from main import VerifyMasterlistRequest, normalize_name, verify_masterlist


def verify(records, registrar_students):
    return verify_masterlist(
        VerifyMasterlistRequest(records=records, registrar_students=registrar_students)
    )


def registrar_student(**overrides):
    values = {
        "id": 10,
        "student_name": "Ana Marie Cruz",
        "campus_id": 2,
        "enrollment_status": "enrolled",
        "cor_printed": True,
    }
    values.update(overrides)
    return values


def test_normalizes_case_punctuation_spacing_and_name_order():
    assert normalize_name("  CRUZ, Ana   Marie ") == normalize_name("ana marie cruz")


def test_returns_enrolled_for_unique_enrolled_student_with_printed_cor():
    result = verify([{"row_id": 1, "student_name": "Cruz, Ana Marie"}], [registrar_student()])

    assert result.records[0].status == "enrolled"
    assert result.records[0].matched_student_id == 10
    assert result.records[0].campus_id == 2
    assert result.summary.enrolled_count == 1


def test_returns_no_cor_printed_for_enrolled_student_without_cor():
    result = verify(
        [{"row_id": 1, "student_name": "Ana Marie Cruz"}],
        [registrar_student(cor_printed=False)],
    )

    assert result.records[0].status == "no_cor_printed"
    assert result.summary.no_cor_printed_count == 1


def test_returns_unenrolled_when_there_is_no_enrolled_match():  
    result = verify(
        [{"row_id": 1, "student_name": "Unknown Student"}],
        [registrar_student(enrollment_status="not_enrolled")],
    )

    assert result.records[0].status == "unenrolled"
    assert result.summary.unenrolled_count == 1


def test_blank_and_ambiguous_names_are_not_matched():
    result = verify(
        [{"row_id": 1, "student_name": " "}, {"row_id": 2, "student_name": "Ana Marie Cruz"}],
        [registrar_student(id=10), registrar_student(id=11, campus_id=3)],
    )

    assert [record.status for record in result.records] == ["unenrolled", "unenrolled"]
    assert "required" in result.records[0].remarks.lower()
    assert "multiple" in result.records[1].remarks.lower()
