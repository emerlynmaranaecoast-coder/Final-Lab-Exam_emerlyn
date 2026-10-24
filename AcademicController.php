<?php
/** AcademicController - add, edit and delete a student's academic records. */
class AcademicController extends Controller
{
    private const RULES = [
        'course_id'    => 'required',
        'semester'     => 'required|in:First Semester,Second Semester,Third Semester',
        'school_year'  => 'required|pattern:/^\d{4}-\d{4}$/',
        'year_level'   => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
        'begin_status' => 'required|in:New,Regular,Irregular,Returnee,Transferee',
        'end_status'   => 'required|in:Ongoing,Completed,Dropped,Failed',
    ];

    /** Form to add an academic record: ?r=academics/create&student=ID */
    public function create(): void
    {
        $student = Student::find($this->id('student')) ?? $this->notFound('student');
        $this->renderForm($student, null, ['end_status' => 'Ongoing', 'begin_status' => 'Regular'], []);
    }

    public function store(): void
    {
        $this->requirePost();
        $student = Student::find($this->id('student')) ?? $this->notFound('student');
        $input = $this->input();
        $errors = $this->validate($input, (int) $student['id']);

        if ($errors) {
            $this->renderForm($student, null, $input, $errors);
            return;
        }
        $id = Academic::create($input + ['student_id' => $student['id']]);
        Logger::info('academic.created', ['id' => $id, 'student_id' => $student['id']]);
        flash('success', 'Academic record added.');
        $this->redirect('students/show', ['id' => $student['id']]);
    }

    public function edit(): void
    {
        [$record, $student] = $this->load();
        $this->renderForm($student, $record, $record, []);
    }

    public function update(): void
    {
        $this->requirePost();
        [$record, $student] = $this->load();
        $input = $this->input();
        $errors = $this->validate($input, (int) $student['id'], (int) $record['id']);

        if ($errors) {
            $this->renderForm($student, $record, $input, $errors);
            return;
        }
        Academic::update((int) $record['id'], $input);
        Logger::info('academic.updated', ['id' => $record['id'], 'student_id' => $student['id']]);
        flash('success', 'Academic record updated.');
        $this->redirect('students/show', ['id' => $student['id']]);
    }

    public function delete(): void
    {
        $this->requirePost();
        [$record, $student] = $this->load();
        Academic::delete((int) $record['id']);
        Logger::info('academic.deleted', ['id' => $record['id'], 'student_id' => $student['id']]);
        flash('success', 'Academic record deleted.');
        $this->redirect('students/show', ['id' => $student['id']]);
    }

    // ---- helpers ----------------------------------------------------------

    /** Load the academic record (?id=) and its student. */
    private function load(): array
    {
        $record = Academic::find($this->id()) ?? $this->notFound('academic record');
        $student = Student::find((int) $record['student_id']) ?? $this->notFound('student');
        return [$record, $student];
    }

    private function renderForm(array $student, ?array $record, array $values, array $errors): void
    {
        $courses = Course::options();
        $this->render('academics/form', compact('student', 'record', 'values', 'errors', 'courses'));
    }

    private function input(): array
    {
        $in = [];
        foreach (array_keys(self::RULES) as $f) {
            $in[$f] = $this->post($f);
        }
        return $in;
    }

    private function validate(array $input, int $studentId, int $exceptId = 0): array
    {
        $errors = Validator::validate($input, self::RULES, [
            'course_id' => 'Course', 'school_year' => 'School year', 'year_level' => 'Year level',
            'begin_status' => 'Beginning-of-semester status', 'end_status' => 'End-of-semester status',
        ]);
        if (!isset($errors['course_id']) && !Course::find((int) $input['course_id'])) {
            $errors['course_id'] = 'Please choose a valid course.';
        }
        if (!isset($errors['school_year'])) {
            [$from, $to] = array_map('intval', explode('-', $input['school_year']));
            if ($to !== $from + 1) {
                $errors['school_year'] = 'School year must span consecutive years, e.g. 2024-2025.';
            }
        }
        if (!$errors && Academic::termExists($studentId, $input['semester'], $input['school_year'], $exceptId)) {
            $errors['semester'] = 'This student already has a record for that semester and school year.';
        }
        return $errors;
    }
}
