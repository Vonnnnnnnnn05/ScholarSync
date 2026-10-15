"""PDF, XLSX, and CSV file parsers for the Grantee Ingestion Pipeline.

Each parser accepts raw file bytes and returns a tuple of:
  (raw_headers: list[str], rows: list[dict[str, str | None]])
"""

import csv
import io
import logging
import os
import re
import tempfile
from typing import Any

logger = logging.getLogger(__name__)


def _decode_csv_bytes(file_bytes: bytes) -> str:
    """Decode CSV bytes using common encodings, with fallback to cp1252/latin-1."""
    encodings = ["utf-8-sig", "utf-8", "cp1252", "iso-8859-1"]
    for enc in encodings:
        try:
            return file_bytes.decode(enc)
        except (UnicodeDecodeError, LookupError):
            continue
    return file_bytes.decode("utf-8", errors="replace")


def parse_csv(file_bytes: bytes) -> tuple[list[str], list[dict[str, str | None]]]:
    """Parse CSV bytes into headers and row dicts with encoding & delimiter resilience."""
    text = _decode_csv_bytes(file_bytes)
    sample = text[:4096]

    # Attempt delimiter detection
    delimiter = ","
    try:
        sniffer = csv.Sniffer()
        dialect = sniffer.sniff(sample, delimiters=",;\t|")
        delimiter = dialect.delimiter
    except Exception:
        if ";" in sample and sample.count(";") > sample.count(","):
            delimiter = ";"
        elif "\t" in sample and sample.count("\t") > sample.count(","):
            delimiter = "\t"

    reader = csv.reader(io.StringIO(text), delimiter=delimiter)
    raw_rows = list(reader)
    if not raw_rows:
        return [], []

    # Find the header row (first non-empty row)
    header_idx = -1
    for i, row in enumerate(raw_rows):
        if any(cell and cell.strip() for cell in row):
            header_idx = i
            break

    if header_idx == -1:
        return [], []

    raw_headers = raw_rows[header_idx]
    headers: list[str] = []
    for i, col in enumerate(raw_headers):
        c = (col or "").strip()
        headers.append(c if c else f"column_{i + 1}")

    rows: list[dict[str, str | None]] = []
    for row_values in raw_rows[header_idx + 1:]:
        # Skip completely blank lines
        if not any(cell and cell.strip() for cell in row_values):
            continue

        row_dict: dict[str, str | None] = {}
        for i, header in enumerate(headers):
            val = row_values[i].strip() if i < len(row_values) and row_values[i] is not None else None
            row_dict[header] = val if val != "" else None
        rows.append(row_dict)

    return headers, rows


def parse_xlsx(file_bytes: bytes) -> tuple[list[str], list[dict[str, str | None]]]:
    """Parse XLSX bytes into headers and row dicts using openpyxl.

    Includes header row detection to skip title/banner rows commonly found in agency spreadsheets.
    """
    from openpyxl import load_workbook

    wb = load_workbook(filename=io.BytesIO(file_bytes), read_only=True, data_only=True)
    ws = wb.active
    if ws is None:
        wb.close()
        return [], []

    all_raw_rows = list(ws.iter_rows(values_only=True))
    wb.close()

    if not all_raw_rows:
        return [], []

    # Keywords that suggest a row is the actual table header
    header_keywords = {"name", "student", "beneficiary", "id", "campus", "course", "program", "no", "first", "last"}

    best_header_idx = 0
    max_keyword_matches = 0

    for i, row in enumerate(all_raw_rows[:15]):  # inspect first 15 rows
        if not row:
            continue
        cells = [str(c).strip().lower() for c in row if c is not None and str(c).strip()]
        if len(cells) < 2:
            continue
        matches = sum(1 for c in cells if any(kw in c for kw in header_keywords))
        if matches > max_keyword_matches:
            max_keyword_matches = matches
            best_header_idx = i

    header_row = all_raw_rows[best_header_idx]
    headers: list[str] = []
    for i, cell in enumerate(header_row):
        val = str(cell).strip() if cell is not None and str(cell).strip() != "" else f"column_{i + 1}"
        headers.append(val)

    rows: list[dict[str, str | None]] = []
    for row_values in all_raw_rows[best_header_idx + 1:]:
        if not row_values or all(cell is None or str(cell).strip() == "" for cell in row_values):
            continue
        row_dict: dict[str, str | None] = {}
        for i, header in enumerate(headers):
            val = row_values[i] if i < len(row_values) else None
            if val is not None:
                # If numeric float ended with .0 (like an ID or integer), format as int
                if isinstance(val, float) and val.is_integer():
                    cleaned = str(int(val))
                else:
                    cleaned = str(val).strip()
                row_dict[header] = cleaned if cleaned else None
            else:
                row_dict[header] = None
        rows.append(row_dict)

    return headers, rows


def _extract_pdf_with_pypdf(file_bytes: bytes) -> tuple[list[str], list[dict[str, str | None]]]:
    """Fallback text extraction using pypdf when tabula is unavailable or finds no tables."""
    try:
        from pypdf import PdfReader

        reader = PdfReader(io.BytesIO(file_bytes))
        lines: list[str] = []
        for page in reader.pages:
            text = page.extract_text() or ""
            for line in text.splitlines():
                stripped = line.strip()
                if stripped:
                    lines.append(stripped)

        if not lines:
            return [], []

        # Find line with multiple columns or delimiters
        headers: list[str] = ["student_name", "student_id", "campus", "program"]
        rows: list[dict[str, str | None]] = []

        for line in lines:
            # Check for comma-separated or tab-separated text lines
            parts = re.split(r"[\t,;|]", line)
            if len(parts) >= 2:
                parts = [p.strip() for p in parts if p.strip()]
                row_dict = {
                    "student_name": parts[0] if len(parts) > 0 else None,
                    "student_id": parts[1] if len(parts) > 1 else None,
                    "campus": parts[2] if len(parts) > 2 else None,
                    "program": parts[3] if len(parts) > 3 else None,
                }
                rows.append(row_dict)

        return headers, rows
    except Exception:
        logger.exception("pypdf text fallback failed")
        return [], []


PROGRAM_PATTERNS = [
    (re.compile(r"tulong[\s\-_–—]*dunong|\btdp\b", re.I), "TDP"),
    (re.compile(r"ched\s+merit|\bcmsp\b", re.I), "CMSP"),
    (re.compile(r"tertiary\s+education\s+subsidy|tes[\s\-_–—]*unifast|\bunifast\b|\btes\b", re.I), "TES-UniFAST"),
    (re.compile(r"dost[\s\-_–—]*sei|\bdost\b|science\s+and\s+technology\s+scholarship", re.I), "DOST-SEI"),
    (re.compile(r"ched[\s\-_–—]*stufaps?|\bstufaps?\b|\bfap\b|student\s+financial\s+assistance", re.I), "CHED-StuFAP"),
    (re.compile(r"provincial\s+(?:scholarship|educational\s+assistance|aid)|lgu\s+(?:scholarship|grant)", re.I), "Provincial / LGU Assistance"),
]

NON_STUDENT_PATTERNS = re.compile(
    r"(commission\s+on\s+higher|republic\s+of\s+the|office\s+of|department\s+of|"
    r"page\s+\d+|list\s+of\s+grantees|list\s+of\s+students|awardees?|masterlist|ched\s+region|"
    r"academic\s+year|semester|\bsem\b|\bay\b\s*\d+|signature|certified\s+correct|"
    r"financial\s+assistance|who\s+availed|availed\s+scholarship|scholarship\s+and\s+financial|"
    r"scholarship\s+programs?|financial\s+assistance\s+programs?|"
    r"^\s*no\.\s*$|^\s*name\s*$|^\s*course\s*$|^\s*campus\s*$|^\s*program\s*$)",
    re.I,
)


def extract_coordinate_pdf(
    file_bytes: bytes, filename: str = "document.pdf"
) -> tuple[list[str], list[dict[str, Any]]]:
    """Extract text from agency PDFs using bounding box coordinates and column isolation.

    Prevents horizontal text collisions in 2-column layouts (Left Column x0 ~ 90, Right Column x0 ~ 302-348)
    and maintains an active scholarship program state machine across banners.
    """
    from header_mapper import HeaderMapper

    try:
        import pdfplumber
    except ImportError:
        logger.warning("pdfplumber not installed; coordinate extraction skipped.")
        return [], []

    active_program = "TES-UniFAST"
    rows: list[dict[str, Any]] = []
    headers = [
        "student_name",
        "first_name",
        "middle_name",
        "last_name",
        "student_id",
        "scholarship_name",
        "scholarship_program",
        "source_pdf_name",
        "raw_agency_name",
    ]

    try:
        with pdfplumber.open(io.BytesIO(file_bytes)) as pdf:
            for page_idx, page in enumerate(pdf.pages):
                lines = page.extract_text_lines()
                if not lines:
                    continue

                # 1. Identify category banners and their vertical positions
                banners: list[tuple[float, float, str, str]] = []
                for l in lines:
                    txt = l["text"].strip()
                    for pat, prog in PROGRAM_PATTERNS:
                        if pat.search(txt):
                            banners.append((l["top"] - 4, l["bottom"] + 6, prog, txt))
                            break

                # 2. Partition page into vertical bands between banners
                slices: list[tuple[float, float, str]] = []
                if not banners:
                    slices.append((0, page.height, active_program))
                else:
                    banners.sort(key=lambda b: b[0])
                    current_top = 0
                    for b_idx, (b_top, b_bottom, prog, _) in enumerate(banners):
                        # On the first page (page_idx == 0), text above the initial program banner
                        # is document-level title/header content (e.g. "LIST OF STUDENTS..."), not student rows.
                        if page_idx == 0 and b_idx == 0:
                            active_program = prog
                            current_top = b_bottom
                            continue

                        if b_top > current_top + 10:
                            slices.append((current_top, b_top, active_program))
                        active_program = prog
                        current_top = b_bottom
                    if current_top < page.height:
                        slices.append((current_top, page.height, active_program))

                # 3. In each vertical band, isolate Left and Right columns
                mid_x = page.width / 2.0
                for s_top, s_bottom, prog in slices:
                    if s_bottom - s_top < 15:
                        continue

                    # If page is standard letter/A4, use two-column bounds
                    for col_name, (x0, x1) in [("Left", (0, mid_x)), ("Right", (mid_x, page.width))]:
                        try:
                            crop = page.crop((x0, s_top, x1, s_bottom))
                            col_lines = crop.extract_text_lines()
                        except Exception:
                            continue

                        for cl in col_lines:
                            line_text = cl["text"].strip()
                            if not line_text or len(line_text) < 3:
                                continue
                            if NON_STUDENT_PATTERNS.search(line_text):
                                continue
                            if any(pat.search(line_text) for pat, _ in PROGRAM_PATTERNS):
                                continue

                            # Detect optional student ID inside line (e.g. 2024-00123 or 24890)
                            extracted_id = None
                            id_match = re.search(r"\b(\d{4}[-\s]\d{4,6}|\d{5,10})\b", line_text)
                            if id_match:
                                extracted_id = id_match.group(1).replace(" ", "-")

                            # Clean student name: strip numbering and extracted ID
                            clean_name = re.sub(r"^\d+[\.\)]\s*", "", line_text).strip()
                            if extracted_id:
                                clean_name = clean_name.replace(id_match.group(0), "").strip()
                            clean_name = re.sub(r"^[,\-\s]+|[,\-\s]+$", "", clean_name).strip()

                            if not clean_name or len(clean_name) < 3:
                                continue
                            if NON_STUDENT_PATTERNS.search(clean_name):
                                continue

                            # Sanitize and split name
                            sanitized = HeaderMapper.sanitize_text(clean_name)
                            split = HeaderMapper.split_full_name(sanitized)

                            rows.append({
                                "student_name": sanitized,
                                "first_name": split["first_name"],
                                "middle_name": split["middle_name"],
                                "last_name": split["last_name"],
                                "student_id": extracted_id,
                                "scholarship_name": prog,
                                "scholarship_program": prog,
                                "source_pdf_name": filename,
                                "raw_agency_name": line_text,
                                "is_incomplete_name": split.get("is_incomplete", False),
                            })

        return headers, rows

    except Exception:
        logger.exception("extract_coordinate_pdf failed")
        return [], []


def parse_pdf(file_bytes: bytes, filename: str = "document.pdf") -> tuple[list[str], list[dict[str, str | None]]]:
    """Parse PDF bytes into headers and row dicts.

    Primary: Coordinate-aware PDF parser with column isolation and banner state machine.
    Fallback: tabula-py across ALL pages.
    Final Fallback: pypdf text extraction.
    """
    # 1. Primary extractor: Coordinate-aware extraction
    headers, rows = extract_coordinate_pdf(file_bytes, filename)
    if rows:
        return headers, rows

    # 2. Secondary extractor: tabula-py
    tmp_path = None
    try:
        import pandas as pd
        import tabula

        # Use temporary file on disk for Windows subprocess compatibility with Java
        with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp:
            tmp.write(file_bytes)
            tmp_path = tmp.name

        tables = tabula.read_pdf(
            tmp_path,
            pages="all",
            multiple_tables=True,
            lattice=True,
            pandas_options={"header": 0},
        )

        if not tables:
            # Retry with stream mode for borderless tables
            tables = tabula.read_pdf(
                tmp_path,
                pages="all",
                multiple_tables=True,
                stream=True,
                pandas_options={"header": 0},
            )

        if not tables:
            return _extract_pdf_with_pypdf(file_bytes)

        # Filter out empty tables
        valid_tables = [df for df in tables if df is not None and not df.empty and len(df.columns) > 1]
        if not valid_tables:
            return _extract_pdf_with_pypdf(file_bytes)

        # Primary headers from first valid table
        primary_headers = [str(col).strip() for col in valid_tables[0].columns.tolist()]

        rows_out: list[dict[str, str | None]] = []

        # Combine rows from ALL tables across ALL pages
        for table in valid_tables:
            # If column counts match, standardize column names
            if len(table.columns) == len(primary_headers):
                table.columns = primary_headers

            for _, row in table.iterrows():
                row_dict: dict[str, str | None] = {}
                is_blank = True
                for header in primary_headers:
                    val = row.get(header)
                    if val is not None and str(val).strip() and str(val).lower() != "nan":
                        row_dict[header] = str(val).strip()
                        is_blank = False
                    else:
                        row_dict[header] = None
                if not is_blank:
                    row_dict["source_pdf_name"] = filename
                    row_dict["scholarship_program"] = "TES-UniFAST"
                    rows_out.append(row_dict)

        return primary_headers, rows_out

    except Exception:
        logger.exception("tabula-py PDF extraction failed, falling back to pypdf")
        return _extract_pdf_with_pypdf(file_bytes)
    finally:
        if tmp_path and os.path.exists(tmp_path):
            try:
                os.remove(tmp_path)
            except OSError:
                pass


def detect_file_type(filename: str) -> str | None:
    """Detect file type from filename extension."""
    lower = filename.lower()
    if lower.endswith(".csv"):
        return "csv"
    if lower.endswith(".xlsx") or lower.endswith(".xls"):
        return "xlsx"
    if lower.endswith(".pdf"):
        return "pdf"
    return None


def parse_file(file_bytes: bytes, filename: str) -> tuple[str, list[str], list[dict[str, Any]]]:
    """Dispatch to the appropriate parser based on file type.

    Returns (file_type, raw_headers, rows).
    """
    file_type = detect_file_type(filename)
    if file_type == "csv":
        headers, rows = parse_csv(file_bytes)
    elif file_type == "xlsx":
        headers, rows = parse_xlsx(file_bytes)
    elif file_type == "pdf":
        headers, rows = parse_pdf(file_bytes, filename)
    else:
        raise ValueError(f"Unsupported file type: {filename}")

    # Ensure source_pdf_name and default scholarship_program are attached
    for r in rows:
        r.setdefault("source_pdf_name", filename)
        r.setdefault("scholarship_program", "TES-UniFAST")

    return file_type, headers, rows
