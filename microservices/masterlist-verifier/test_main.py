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
