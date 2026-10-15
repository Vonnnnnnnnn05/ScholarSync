"""Header normalization and name-splitting logic for the Grantee Ingestion Pipeline.

Maps varied external-agency column headers to a standardized schema and splits
full names into first/middle/last components with support for Filipino compound names,
Unicode homoglyphs (Greek/Cyrillic to Latin), comma spacing, and incomplete entries.
"""

import re
import unicodedata
from typing import Any

# Maps normalized field names to accepted aliases (all lowercase, underscored).
HEADER_ALIAS_MAP: dict[str, list[str]] = {
    "first_name": ["first_name", "firstname", "first", "given_name", "given", "fname"],
    "middle_name": ["middle_name", "middlename", "middle", "mi", "middle_initial", "mname"],
    "last_name": ["last_name", "lastname", "last", "surname", "family_name", "lname"],
    "student_name": [
        "student_name", "name", "beneficiary", "beneficiary_name",
        "awardee", "awardee_name", "scholar_name", "full_name",
        "scholar", "student", "grantee_name", "grantee", "fullname",
    ],
    "student_id": [
        "student_id", "student_id_number", "stud_no", "id_number",
        "student_number", "school_id", "id_no", "studno", "id",
        "student_no", "lrn", "id_num",
    ],
    "campus": ["campus", "campus_name", "school", "branch", "campus_code", "campuses"],
    "program": [
        "program", "course", "degree", "academic_program", "course_name",
        "curriculum", "program_enrolled",
    ],
    "scholarship_name": [
        "scholarship_name", "scholarship", "grant", "grant_name",
        "program_name", "scholarship_program", "type_of_scholarship", "category",
    ],
    "year_level": ["year_level", "year", "yr", "level", "yr_level"],
}

# Build a reverse lookup: alias → normalized field name.
_ALIAS_LOOKUP: dict[str, str] = {}
for _field, _aliases in HEADER_ALIAS_MAP.items():
    for _alias in _aliases:
        _ALIAS_LOOKUP[_alias] = _field

# Known Filipino compound surname prefixes
COMPOUND_SURNAME_PREFIXES = [
    "de la", "dela", "de los", "delos", "de las", "delas",
    "san", "santa", "sta.", "sto.", "santo", "del", "de",
    "van", "von",
]

# Unicode Homoglyph translation table (Greek and Cyrillic lookalikes to ASCII Latin)
HOMOGLYPH_MAP: dict[str, str] = {
    # Greek uppercase
    "\u0391": "A",  # Α
    "\u0392": "B",  # Β
    "\u0395": "E",  # Ε
    "\u0396": "Z",  # Ζ
    "\u0397": "H",  # Η
    "\u0399": "I",  # Ι
    "\u039A": "K",  # Κ
    "\u039C": "M",  # Μ
    "\u039D": "N",  # Ν
    "\u039F": "O",  # Ο
    "\u03A1": "P",  # Ρ
    "\u03A4": "T",  # Τ
    "\u03A5": "Y",  # Υ
    "\u03A7": "X",  # Χ
    # Cyrillic uppercase
    "\u0410": "A",  # А
    "\u0412": "B",  # В
    "\u0415": "E",  # Е
    "\u041A": "K",  # К
    "\u041C": "M",  # М
    "\u041D": "H",  # Н
    "\u041E": "O",  # О
    "\u0420": "P",  # Р
    "\u0421": "C",  # С
    "\u0422": "T",  # Т
    "\u0423": "Y",  # У
    "\u0425": "X",  # Х
    # Accented letters
    "\u00D1": "N",  # Ñ
    "\u00F1": "n",  # ñ
}


def sanitize_text(text: str | None) -> str:
    """Normalize comma spacing, Greek/Cyrillic homoglyphs, and diacritics."""
    if not text:
        return ""

    s = str(text)

    # 1. Replace homoglyphs
    for char, rep in HOMOGLYPH_MAP.items():
        if char in s:
            s = s.replace(char, rep)

    # 2. Normalize N-tilde and decompose accents if needed
    s = s.replace("\u00D1", "N").replace("\u00F1", "n")

    # 3. Fix comma spacing: e.g. "LASTNAME ,FIRSTNAME" -> "LASTNAME, FIRSTNAME"
    s = re.sub(r"\s*,\s*", ", ", s)

    # 4. Collapse extra whitespace
    s = re.sub(r"\s+", " ", s).strip()

    return s


def _normalize_header_text(raw: str) -> str:
    """Normalize a single raw header string to a canonical form for matching."""
    cleaned = sanitize_text(raw)
    return re.sub(r"[^a-z0-9]+", "_", cleaned.lower()).strip("_")


def normalize_headers(raw_headers: list[str]) -> tuple[list[str], list[str], list[str]]:
    """Normalize raw headers from an uploaded file.

    Returns:
        (normalized_headers, unmapped_headers, missing_expected_fields)
    """
    normalized: list[str] = []
    unmapped: list[str] = []

    for raw in raw_headers:
        key = _normalize_header_text(raw)
        mapped = _ALIAS_LOOKUP.get(key)
        if mapped:
            normalized.append(mapped)
        else:
            normalized.append(key)
            unmapped.append(raw)

    missing = detect_missing_fields(normalized)
    return normalized, unmapped, missing


def detect_missing_fields(normalized_headers: list[str]) -> list[str]:
    """Detect which required or recommended fields are missing from normalized headers."""
    found = set(normalized_headers)
    has_name = "student_name" in found or all(f in found for f in ("first_name", "last_name"))
    missing: list[str] = []
    if not has_name:
        missing.append("student_name")
    if "student_id" not in found:
        missing.append("student_id")
    if "campus" not in found:
        missing.append("campus")
    if "program" not in found:
        missing.append("program")
    return missing


def normalize_row(row: dict[str, str | None], normalized_headers: list[str], raw_headers: list[str]) -> dict[str, Any]:
    """Normalize a single row using the mapped headers.

    Returns a dict with standardized field names, sanitized text, and split name components.
    """
    mapped: dict[str, str | None] = {}
    for raw_h, norm_h in zip(raw_headers, normalized_headers):
        val = row.get(raw_h)
        if val is not None and str(val).strip():
            mapped[norm_h] = sanitize_text(str(val))
        else:
            mapped.setdefault(norm_h, None)

    # Name splitting: if we have student_name but not first/last, split it.
    is_incomplete = False
    if mapped.get("student_name") and not mapped.get("first_name"):
        split = split_full_name(mapped["student_name"] or "")
        mapped["first_name"] = split["first_name"]
        mapped["middle_name"] = split["middle_name"]
        mapped["last_name"] = split["last_name"]
        is_incomplete = split.get("is_incomplete", False)
    elif mapped.get("first_name") or mapped.get("last_name"):
        parts = [mapped.get("first_name"), mapped.get("middle_name"), mapped.get("last_name")]
        mapped["student_name"] = " ".join(p for p in parts if p)
        if not mapped.get("first_name") and mapped.get("last_name"):
            is_incomplete = True

    mapped["is_incomplete_name"] = is_incomplete
    return mapped


def split_full_name(full_name: str) -> dict[str, Any]:
    """Split a full name into first, middle, and last name components.

    Handles:
      - Homoglyph normalization (Greek M, A, E, O, K -> Latin M, A, E, O, K)
      - Comma spacing normalization ("LAST ,FIRST" -> "LAST, FIRST")
      - Filipino naming conventions: "LAST, FIRST MIDDLE"
      - Compound surnames: "DELA CRUZ", "SAN JOSE", "STA. MARIA"
      - Surname-only / Incomplete names: "BALASAG" -> last_name="BALASAG", first_name=None, is_incomplete=True
    """
    result: dict[str, Any] = {
        "first_name": None,
        "middle_name": None,
        "last_name": None,
        "is_incomplete": False,
    }

    name = sanitize_text(full_name)
    if not name:
        result["is_incomplete"] = True
        return result

    # Pattern 1: Comma-separated "LAST, FIRST [MIDDLE INITIAL]"
    if "," in name:
        parts = [p.strip() for p in name.split(",", 1)]
        result["last_name"] = parts[0]
        if len(parts) > 1 and parts[1]:
            given_raw = parts[1].strip()
            tokens = [p.strip(",. ") for p in given_raw.split() if p.strip(",. ")]
            # If the last token is a single-letter initial (e.g. 'M' or 'M.'), treat it as middle initial.
            # Never truncate multi-word first names (e.g. 'PRECIOUS ELLA', 'HANNAH FAITH', 'GWYNETH LAYE LLAINE').
            if len(tokens) >= 2 and len(tokens[-1]) == 1:
                result["first_name"] = " ".join(tokens[:-1])
                result["middle_name"] = tokens[-1]
            elif tokens:
                result["first_name"] = " ".join(tokens)
            else:
                result["is_incomplete"] = True
        else:
            result["is_incomplete"] = True
        return result

    # Pattern 2: Space-separated
    tokens = name.split()
    if len(tokens) == 1:
        # Surname only
        result["last_name"] = tokens[0]
        result["is_incomplete"] = True
        return result

    if len(tokens) == 2:
        result["first_name"] = tokens[0]
        result["last_name"] = tokens[1]
        return result

    # Check for compound surname at the end
    lower_tokens = [t.lower() for t in tokens]
    compound_matched_idx = -1

    for prefix in COMPOUND_SURNAME_PREFIXES:
        p_tokens = prefix.split()
        p_len = len(p_tokens)
        if len(tokens) > p_len:
            candidate_start = len(tokens) - 1 - p_len
            if candidate_start >= 1 and lower_tokens[candidate_start:len(tokens) - 1] == p_tokens:
                compound_matched_idx = candidate_start
                break

    if compound_matched_idx != -1:
        result["last_name"] = " ".join(tokens[compound_matched_idx:])
        given = tokens[:compound_matched_idx]
        if len(given) > 1 and len(given[-1]) <= 2:  # middle initial
            result["first_name"] = " ".join(given[:-1])
            result["middle_name"] = given[-1].rstrip(".")
        else:
            result["first_name"] = " ".join(given)
        return result

    # Default heuristic: Last token is surname, second-to-last is middle initial if short
    result["first_name"] = tokens[0]
    if len(tokens) > 2:
        result["middle_name"] = " ".join(tokens[1:-1])
    result["last_name"] = tokens[-1]
    return result


class HeaderMapper:
    """Convenience class wrapper around header normalization functions."""

    @staticmethod
    def sanitize_text(text: str | None) -> str:
        return sanitize_text(text)

    @staticmethod
    def normalize_headers(raw_headers: list[str]) -> tuple[list[str], list[str], list[str]]:
        return normalize_headers(raw_headers)

    @staticmethod
    def normalize_row(row: dict[str, str | None], normalized_headers: list[str], raw_headers: list[str]) -> dict[str, Any]:
        return normalize_row(row, normalized_headers, raw_headers)

    @staticmethod
    def split_full_name(full_name: str) -> dict[str, Any]:
        return split_full_name(full_name)

    @staticmethod
    def detect_missing_fields(normalized_headers: list[str]) -> list[str]:
        return detect_missing_fields(normalized_headers)
