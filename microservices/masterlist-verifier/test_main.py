from main import VerifyMasterlistRequest, normalize_name, verify_masterlist


def verify(records, registrar_students):
    return verify_masterlist(
        VerifyMasterlistRequest(records=records, registrar_students=registrar_students)
    )


def registrar_student(**overrides):
    values = {
        "id": 10,
        "student_id_number": "2024-001",
        "student_name": "Ana Marie Cruz",
        "campus_id": 2,
        "enrollment_status": "enrolled",
        "cor_printed": True,
    }
    values.update(overrides)
    return values


def test_normalizes_case_punctuation_spacing_and_name_order():
    assert normalize_name("  CRUZ, Ana   Marie ") == normalize_name("ana marie cruz")


def test_exact_student_id_match_returns_separate_qualified_results():
    result = verify(
        [{"row_id": 1, "student_id_number": "2024-001", "student_name": "Wrong Name", "campus_id": 2}],
        [registrar_student()],
    )

    record = result.records[0]
    assert record.match_status == "matched"
    assert record.enrollment_status == "enrolled"
    assert record.cor_status == "cor_printed"
    assert record.qualification_status == "qualified"
    assert record.matched_student_id == 10


def test_unique_normalized_name_match_without_cor_is_not_qualified():
    result = verify(
        [{"row_id": 1, "student_name": "Cruz, Ana Marie", "campus_id": 2}],
        [registrar_student(cor_printed=False)],
    )

    record = result.records[0]
    assert record.enrollment_status == "enrolled"
    assert record.cor_status == "no_cor_printed"
    assert record.qualification_status == "not_qualified"


def test_confident_official_not_enrolled_record_is_not_qualified():
    result = verify(
        [{"row_id": 1, "student_id_number": "2024-001", "student_name": "Ana Marie Cruz", "campus_id": 2}],
        [registrar_student(enrollment_status="not_enrolled")],
    )

    record = result.records[0]
    assert record.enrollment_status == "not_enrolled"
    assert record.cor_status == "cor_printed"
    assert record.qualification_status == "not_qualified"


def test_unmatched_blank_and_ambiguous_records_need_review():
    result = verify(
        [
            {"row_id": 1, "student_name": "Unknown Student", "campus_id": 2},
            {"row_id": 2, "student_name": " ", "campus_id": 2},
            {"row_id": 3, "student_name": "Ana Marie Cruz", "campus_id": 2},
        ],
        [registrar_student(id=10), registrar_student(id=11, student_id_number="2024-002")],
    )

    assert [record.match_status for record in result.records] == ["unmatched", "unmatched", "ambiguous"]
    assert all(record.enrollment_status == "needs_review" for record in result.records)
    assert all(record.cor_status == "needs_review" for record in result.records)
    assert all(record.qualification_status == "needs_review" for record in result.records)


def test_cross_campus_candidate_needs_review_instead_of_not_enrolled():
    result = verify(
        [{"row_id": 1, "student_id_number": "2024-001", "student_name": "Ana Marie Cruz", "campus_id": 1}],
        [registrar_student(campus_id=2)],
    )

    record = result.records[0]
    assert record.match_status == "inconsistent"
    assert record.enrollment_status == "needs_review"
    assert "campus" in record.remarks.lower()


def test_minor_spelling_difference_returns_safe_same_campus_suggestion():
    result = verify(
        [{"row_id": 1, "student_name": "Von Essson Vergara", "campus_id": 2}],
        [registrar_student(id=25, student_id_number="2026-025", student_name="Von Esson Vergara")],
    )

    record = result.records[0]
    assert record.match_status == "possible_match"
    assert record.matched_student_id == 25
    assert record.similarity_score >= 0.90
    assert record.enrollment_status == "needs_review"
    assert record.cor_status == "needs_review"
    assert record.qualification_status == "needs_review"


def test_close_fuzzy_candidates_are_ambiguous_instead_of_suggested():
    result = verify(
        [{"row_id": 1, "student_name": "Von Essson Vergara", "campus_id": 2}],
        [
            registrar_student(id=25, student_id_number="2026-025", student_name="Von Esson Vergara"),
            registrar_student(id=26, student_id_number="2026-026", student_name="Von Eson Vergara"),
        ],
    )

    record = result.records[0]
    assert record.match_status == "ambiguous"
    assert record.matched_student_id is None
    assert record.qualification_status == "needs_review"


def test_summary_counts_all_records_without_removing_exceptions():
    result = verify(
        [
            {"row_id": 1, "student_id_number": "2024-001", "student_name": "Ana Marie Cruz", "campus_id": 2},
            {"row_id": 2, "student_name": "Unknown", "campus_id": 2},
        ],
        [registrar_student()],
    )

    assert result.summary.total_records == 2
    assert result.summary.qualified_count == 1
    assert result.summary.needs_review_count == 1
    assert len(result.records) == 2


# --- Tests for /verify-grantees (v4.0.0) ---
from main import VerifyGranteesRequest, verify_grantees


def test_verify_grantees_exact_id_green():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_id": "2024-00123", "student_name": "Hannah Faith Odol"}],
        registrar_students=[{"id": 42, "student_id_number": "2024-00123", "student_name": "Hannah Faith Odol", "campus_id": 1}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.green_count == 1
    assert res.records[0].verification_status == "GREEN_VERIFIED"
    assert res.records[0].match_tier == "EXACT_ID"
    assert res.records[0].matched_registrar_student_id == 42


def test_verify_grantees_exact_id_double_grant_amber():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_id": "2024-00123", "student_name": "Hannah Faith Odol"}],
        registrar_students=[{"id": 42, "student_id_number": "2024-00123", "student_name": "Hannah Faith Odol", "campus_id": 1}],
        existing_grantee_student_ids=[42],
    )
    res = verify_grantees(req)
    assert res.summary.amber_count == 1
    assert res.records[0].verification_status == "AMBER_CONFLICT"
    assert res.records[0].conflict_reason == "DOUBLE_GRANT"


def test_verify_grantees_fuzzy_name_green():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_name": "Hannah Fayth Odol", "campus_id": 1}],
        registrar_students=[{"id": 42, "student_id_number": "2024-00123", "student_name": "Hannah Faith Odol", "campus_id": 1}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.green_count == 1
    assert res.records[0].verification_status == "GREEN_VERIFIED"
    assert res.records[0].match_tier == "FUZZY_NAME"


def test_verify_grantees_fuzzy_multi_match_amber():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_name": "Juan Dela Cruz"}],
        registrar_students=[
            {"id": 50, "student_name": "Juan P Dela Cruz", "campus_id": 1},
            {"id": 51, "student_name": "Juan M Dela Cruz", "campus_id": 2},
        ],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.amber_count == 1
    assert res.records[0].verification_status == "AMBER_CONFLICT"
    assert res.records[0].conflict_reason == "MULTI_MATCH"


def test_verify_grantees_unmatched_red():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_id": "9999-99999", "student_name": "Completely Unknown Person"}],
        registrar_students=[{"id": 42, "student_id_number": "2024-00123", "student_name": "Hannah Faith Odol"}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.red_count == 1
    assert res.records[0].verification_status == "RED_UNMATCHED"


def test_verify_grantees_unenrolled_is_red():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_id": "2024-00123", "student_name": "Hannah Faith Odol"}],
        registrar_students=[{"id": 42, "student_id_number": "2024-00123", "student_name": "Hannah Faith Odol", "enrollment_status": "unenrolled"}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.red_count == 1
    assert res.records[0].verification_status == "RED_UNMATCHED"
    assert res.records[0].conflict_reason == "UNENROLLED"


def test_verify_grantees_no_cor_printed_is_amber():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "student_id": "2024-00123", "student_name": "Hannah Faith Odol"}],
        registrar_students=[{"id": 42, "student_id_number": "2024-00123", "student_name": "Hannah Faith Odol", "enrollment_status": "no_cor_printed"}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.amber_count == 1
    assert res.records[0].verification_status == "AMBER_CONFLICT"
    assert res.records[0].conflict_reason == "NO_COR_PRINTED"


def test_verify_grantees_agency_typo_fuzzy_match():
    # Real agency typo: BALASAG vs BALANSAG (similarity >= 0.72)
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "last_name": "BALASAG", "first_name": "JANE", "student_name": "BALASAG, JANE"}],
        registrar_students=[{"id": 88, "student_name": "Jane Balansag", "last_name": "Balansag", "enrollment_status": "enrolled"}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.records[0].confidence_score >= 0.72
    assert res.records[0].matched_registrar_student_id == 88


def test_verify_grantees_cross_category_double_dipping_amber():
    req = VerifyGranteesRequest(
        records=[
            {"row_id": 1, "student_id": "2024-005", "student_name": "Carlo Garcia", "scholarship_program": "TES-UniFAST"},
            {"row_id": 2, "student_id": "2024-005", "student_name": "Carlo Garcia", "scholarship_program": "TDP"},
        ],
        registrar_students=[{"id": 55, "student_id_number": "2024-005", "student_name": "Carlo Garcia", "enrollment_status": "enrolled"}],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.summary.amber_count == 2
    assert res.records[0].conflict_reason == "DOUBLE_GRANT"
    assert "Cross-category conflict" in res.records[0].remarks


def test_verify_grantees_surname_only_incomplete_entry():
    req = VerifyGranteesRequest(
        records=[{"row_id": 1, "last_name": "BALANSAG", "first_name": None, "student_name": "BALANSAG", "is_incomplete_name": True}],
        registrar_students=[
            {"id": 101, "student_name": "Jane Balansag", "last_name": "Balansag", "enrollment_status": "enrolled"},
            {"id": 102, "student_name": "John Balansag", "last_name": "Balansag", "enrollment_status": "enrolled"},
        ],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.records[0].verification_status == "AMBER_CONFLICT"
    assert res.records[0].conflict_reason == "INCOMPLETE_NAME"
    assert len(res.records[0].candidate_matches) == 2


def test_coordinate_pdf_column_separation_and_multi_category_banners():
    import io
    import reportlab.pdfgen.canvas as canvas
    from pdf_parser import parse_pdf

    buf = io.BytesIO()
    c = canvas.Canvas(buf, pagesize=(612, 792))
    # Page 1: TES-UniFAST Banner
    c.drawString(100, 720, "TERTIARY EDUCATION SUBSIDY (TES)")
    c.drawString(90, 680, "1. DELA CRUZ ,JUAN")
    c.drawString(340, 680, "2. SANTOS, MARIA")
    # Page 1: TDP Banner
    c.drawString(100, 500, "TULONG-DUNONG PROGRAM (TDP)")
    c.drawString(90, 460, "3. LUMENDA, RAHIB")
    c.drawString(340, 460, "4. GARCIA, CARLO")
    c.showPage()
    # Page 2: CMSP Banner
    c.drawString(100, 720, "CHED MERIT SCHOLARSHIP PROGRAM (CMSP)")
    c.drawString(90, 680, "5. BAUTISTA, LUIS")
    c.drawString(340, 680, "6. AQUINO, PAOLO")
    c.save()
    buf.seek(0)

    headers, rows = parse_pdf(buf.getvalue(), "agency_multi_cat.pdf")
    assert len(rows) == 6
    # Verify no horizontal collision
    names = [r["student_name"] for r in rows]
    assert "DELA CRUZ, JUAN" in names
    assert "SANTOS, MARIA" in names
    assert "LUMENDA, RAHIB" in names
    assert "GARCIA, CARLO" in names
    assert "BAUTISTA, LUIS" in names
    assert "AQUINO, PAOLO" in names

    # Verify category state machine assignment
    programs = [r["scholarship_program"] for r in rows]
    assert programs[0] == "TES-UniFAST"
    assert programs[1] == "TES-UniFAST"
    assert programs[2] == "TDP"
    assert programs[3] == "TDP"
    assert programs[4] == "CMSP"
    assert programs[5] == "CMSP"


def test_sanitization_greek_homoglyphs_and_comma_spacing():
    from header_mapper import HeaderMapper

    # Greek M (\u039C) and A (\u0391)
    raw = "\u039CENDOZA , \u0391NA"
    sanitized = HeaderMapper.sanitize_text(raw)
    assert sanitized == "MENDOZA, ANA"
    split = HeaderMapper.split_full_name(sanitized)
    assert split["last_name"] == "MENDOZA"
    assert split["first_name"] == "ANA"


def test_parse_files_batch_endpoint():
    from fastapi.testclient import TestClient
    from main import app

    client = TestClient(app)
    csv1 = b'student_name,student_id\n"Cruz, Juan",2024-001\n'
    csv2 = b'student_name,student_id\n"Santos, Maria",2024-002\n'

    files = [
        ("files", ("batch1.csv", csv1, "text/csv")),
        ("files", ("batch2.csv", csv2, "text/csv")),
    ]
    response = client.post("/parse-files", files=files)
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True
    assert data["total_files"] == 2
    assert data["total_rows"] == 2
    assert data["rows"][0]["student_name"] == "Cruz, Juan"
    assert data["rows"][0]["source_pdf_name"] == "batch1.csv"
    assert data["rows"][1]["student_name"] == "Santos, Maria"
    assert data["rows"][1]["source_pdf_name"] == "batch2.csv"


def test_agency_multi_page_registrar_pdf_sample():
    import io
    import reportlab.pdfgen.canvas as canvas
    from pdf_parser import parse_pdf

    buf = io.BytesIO()
    c = canvas.Canvas(buf, pagesize=(612, 792))
    # Page 1: Header + Banner + 4 students
    c.drawCentredString(306, 750, "LIST OF STUDENTS WHO AVAILED SCHOLARSHIP AND")
    c.drawCentredString(306, 735, "FINANCIAL ASSISTANCE PROGRAMS")
    c.drawString(72, 690, "TERTIARY EDUCATION SUBSIDY (TES\u2013UniFAST)")
    c.drawString(72, 660, "ABAD, PRECIOUS ELLA")
    c.drawString(340, 660, "LATIP, SITTIEH MOHAISAH")
    c.drawString(72, 645, "ORCAJADA, HEARTY, MARIE")
    c.drawString(340, 645, "LEGASPI, JAN NEIL")
    c.showPage()
    # Page 4 simulation: 2 students under TES then TDP banner then 2 students
    c.drawString(72, 720, "DUNGOG, DANILLA KAYE")
    c.drawString(340, 720, "TAMAYO, IVAN JADE")
    c.drawString(72, 670, "TULONG-DUNONG PROGRAM (TDP)")
    c.drawString(72, 630, "ABENOJA, MIGUEL ANDRAE")
    c.drawString(340, 630, "LANSANGAN, REY MARK")
    c.showPage()
    # Page 8 simulation: CMSP banner then students with enye
    c.drawString(72, 720, "CHED MERIT SCHOLARSHIP PROGRAM (CMSP)")
    c.drawString(72, 680, "CARI\u00d1O, SHANE DITIANQUIN")
    c.drawString(340, 680, "EMPLEO, CAREL")
    c.showPage()
    c.save()
    buf.seek(0)

    headers, rows = parse_pdf(buf.getvalue(), "registrar_sample.pdf")
    assert len(rows) == 10
    assert rows[0]["student_name"] == "ABAD, PRECIOUS ELLA"
    assert rows[0]["first_name"] == "PRECIOUS ELLA"
    assert rows[0]["last_name"] == "ABAD"
    assert rows[0]["scholarship_program"] == "TES-UniFAST"

    # Edge case: multiple commas in source name
    assert rows[1]["student_name"] == "ORCAJADA, HEARTY, MARIE"
    assert rows[1]["first_name"] == "HEARTY MARIE"
    assert rows[1]["last_name"] == "ORCAJADA"

    # Cross-page transition
    assert rows[4]["student_name"] == "DUNGOG, DANILLA KAYE"
    assert rows[4]["scholarship_program"] == "TES-UniFAST"
    assert rows[6]["student_name"] == "ABENOJA, MIGUEL ANDRAE"
    assert rows[6]["scholarship_program"] == "TDP"

    # Enye normalization
    assert rows[8]["first_name"] == "SHANE DITIANQUIN"
    assert rows[8]["last_name"] == "CARINO"
    assert rows[8]["scholarship_program"] == "CMSP"


def test_verify_grantees_resolves_campus_name():
    from main import VerifyGranteesRequest, verify_grantees

    req = VerifyGranteesRequest(
        records=[
            {"row_id": 1, "student_id": "2024-00123", "student_name": "Hannah Faith Odol", "campus": None},
            {"row_id": 2, "student_name": "Carlo Garcia", "campus": None},
        ],
        registrar_students=[
            {
                "id": 42,
                "student_id_number": "2024-00123",
                "student_name": "Hannah Faith Odol",
                "campus_id": 1,
                "campus": "ACCESS Campus",
                "enrollment_status": "enrolled",
                "cor_printed": True,
            },
            {
                "id": 43,
                "student_name": "Carlo Garcia",
                "campus_id": 3,
                "campus": "Isulan Campus",
                "enrollment_status": "enrolled",
                "cor_printed": True,
            },
        ],
        existing_grantee_student_ids=[],
    )
    res = verify_grantees(req)
    assert res.records[0].resolved_campus_id == 1
    assert res.records[0].resolved_campus == "ACCESS Campus"
    assert res.records[0].verification_status == "GREEN_VERIFIED"

    assert res.records[1].resolved_campus_id == 3
    assert res.records[1].resolved_campus == "Isulan Campus"
    assert res.records[1].verification_status == "GREEN_VERIFIED"





