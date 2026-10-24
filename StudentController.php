<?php
/** StudentController - student information and records management. */
class StudentController extends Controller
{
    private const RULES = [
        'student_roll'      => 'required|max:30|pattern:/^[A-Za-z0-9-]+$/',
        'last_name'         => 'required|max:60',
        'first_name'        => 'required|max:60',
        'middle_name'       => 'max:60',
        'gender'            => 'required|in:Male,Female,Other',
        'birth_date'        => 'required|date',
        'contact_no'        => 'max:20|pattern:/^[0-9+() -]+$/',
        'present_address'   => 'required|max:255',
        'permanent_address' => 'max:255',
    ];

    /** List all students. */
    public function index(): void
    {
        $q = $this->query('q');
        $status = $this->query('status');
        $result = Student::search($q, $status, $this->page(), (int) Config::get('app.per_page', 10));
        $this->render('students/index', compact('result', 'q', 'status'));
    }

    public function create(): void
    {
        $this->render('students/form', ['student' => null, 'values' => ['status' => 'Active'], 'errors' => []]);
    }

    public function store(): void
    {
        $this->requirePost();
        $input = $this->input();
        $errors = $this->validate($input);

        if ($errors) {
            $this->render('students/form', ['student' => null, 'values' => $input, 'errors' => $errors]);
            return;
        }
        $id = Student::create($input);
        Logger::info('student.created', ['id' => $id, 'roll' => $input['student_roll']]);
        flash('success', 'Student added. You can now add academic records.');
        $this->redirect('students/show', ['id' => $id]);
    }

    /** View student details with academic history. */
    public function show(): void
    {
        $student = Student::find($this->id()) ?? $this->notFound('student');
        $history = Academic::forStudent((int) $student['id']);
        $this->render('students/show', compact('student', 'history'));
    }

    public function edit(): void
    {
        $student = Student::find($this->id()) ?? $this->notFound('student');
        $this->render('students/form', ['student' => $student, 'values' => $student, 'errors' => []]);
    }

    public function update(): void
    {
        $this->requirePost();
        $student = Student::find($this->id()) ?? $this->notFound('student');
        $input = $this->input();
        $errors = $this->validate($input, (int) $student['id']);

        if ($errors) {
            $this->render('students/form', ['student' => $student, 'values' => $input, 'errors' => $errors]);
            return;
        }
        Student::update((int) $student['id'], $input);
        Logger::info('student.updated', ['id' => $student['id']]);
        flash('success', 'Student details updated.');
        $this->redirect('students/show', ['id' => $student['id']]);
    }

    /** Update only the student's status. */
    public function status(): void
    {
        $this->requirePost();
        $student = Student::find($this->id()) ?? $this->notFound('student');
        $status = $this->post('status');

        if (!in_array($status, Student::STATUSES, true)) {
            flash('danger', 'Invalid status selected.');
        } else {
            Student::setStatus((int) $student['id'], $status);
            Logger::info('student.status_changed', ['id' => $student['id'], 'from' => $student['status'], 'to' => $status]);
            flash('success', "Status updated to $status.");
        }
        $this->redirect('students/show', ['id' => $student['id']]);
    }

    /** Delete a student and (via foreign key cascade) all their academic records. */
    public function delete(): void
    {
        $this->requirePost();
        $this->requireRole('admin');
        $student = Student::find($this->id()) ?? $this->notFound('student');

        Student::delete((int) $student['id']);
        Logger::info('student.deleted', ['id' => $student['id'], 'roll' => $student['student_roll']]);
        flash('success', 'Student information and records deleted.');
        $this->redirect('students/index');
    }

    // ---- helpers ----------------------------------------------------------

    private function input(): array
    {
        $in = [];
        foreach (['student_roll', 'last_name', 'first_name', 'middle_name', 'gender',
                  'birth_date', 'contact_no', 'present_address', 'permanent_address', 'status'] as $f) {
            $in[$f] = $this->post($f);
        }
        if ($in['status'] === '') {
            $in['status'] = 'Active';
        }
        return $in;
    }

    private function validate(array $input, int $exceptId = 0): array
    {
        $errors = Validator::validate($input, self::RULES, [
            'student_roll' => 'Student roll', 'birth_date' => 'Date of birth', 'contact_no' => 'Contact number',
        ]);
        if (!in_array($input['status'], Student::STATUSES, true)) {
            $errors['status'] = 'Invalid status.';
        }
        if (!isset($errors['student_roll']) && Student::rollExists($input['student_roll'], $exceptId)) {
            $errors['student_roll'] = 'This student roll number already exists.';
        }
        if (!isset($errors['birth_date']) && $input['birth_date'] > date('Y-m-d')) {
            $errors['birth_date'] = 'Date of birth cannot be in the future.';
        }
        return $errors;
    }
}
