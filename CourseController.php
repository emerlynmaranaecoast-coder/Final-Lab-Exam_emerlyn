<?php
/** CourseController - add, list, view, update and delete courses. */
class CourseController extends Controller
{
    private const RULES = [
        'department_id' => 'required',
        'code'          => 'required|max:20|pattern:/^[A-Za-z0-9._-]+$/',
        'name'          => 'required|max:150',
        'units'         => 'pattern:/^\d{1,3}$/',
        'description'   => 'max:500',
    ];

    public function index(): void
    {
        $q = $this->query('q');
        $departmentId = $this->id('department');
        $result = Course::search($q, $departmentId, $this->page(), (int) Config::get('app.per_page', 10));
        $departments = Department::options();
        $this->render('courses/index', compact('result', 'q', 'departmentId', 'departments'));
    }

    public function create(): void
    {
        $values = ['department_id' => $this->id('department')];
        $this->renderForm(null, $values, []);
    }

    public function store(): void
    {
        $this->requirePost();
        $input = $this->input();
        $errors = $this->validate($input);

        if ($errors) {
            $this->renderForm(null, $input, $errors);
            return;
        }
        $id = Course::create($input);
        Logger::info('course.created', ['id' => $id, 'code' => $input['code']]);
        flash('success', 'Course added successfully.');
        $this->redirect('courses/show', ['id' => $id]);
    }

    public function show(): void
    {
        $course = Course::findWithDepartment($this->id()) ?? $this->notFound('course');
        $enrollments = Course::enrollmentCount((int) $course['id']);
        $this->render('courses/show', compact('course', 'enrollments'));
    }

    public function edit(): void
    {
        $course = Course::find($this->id()) ?? $this->notFound('course');
        $this->renderForm($course, $course, []);
    }

    public function update(): void
    {
        $this->requirePost();
        $course = Course::find($this->id()) ?? $this->notFound('course');
        $input = $this->input();
        $errors = $this->validate($input, (int) $course['id']);

        if ($errors) {
            $this->renderForm($course, $input, $errors);
            return;
        }
        Course::update((int) $course['id'], $input);
        Logger::info('course.updated', ['id' => $course['id']]);
        flash('success', 'Course updated successfully.');
        $this->redirect('courses/show', ['id' => $course['id']]);
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->requireRole('admin');
        $course = Course::find($this->id()) ?? $this->notFound('course');

        if (Course::enrollmentCount((int) $course['id']) > 0) {
            flash('danger', 'This course is used in student academic records and cannot be deleted.');
            $this->redirect('courses/show', ['id' => $course['id']]);
        }
        Course::delete((int) $course['id']);
        Logger::info('course.deleted', ['id' => $course['id'], 'code' => $course['code']]);
        flash('success', 'Course deleted.');
        $this->redirect('courses/index');
    }

    // ---- helpers ----------------------------------------------------------

    private function renderForm(?array $course, array $values, array $errors): void
    {
        $departments = Department::options();
        $this->render('courses/form', compact('course', 'values', 'errors', 'departments'));
    }

    private function input(): array
    {
        return [
            'department_id' => $this->post('department_id'),
            'code'          => strtoupper($this->post('code')),
            'name'          => $this->post('name'),
            'units'         => $this->post('units'),
            'description'   => $this->post('description'),
        ];
    }

    private function validate(array $input, int $exceptId = 0): array
    {
        $errors = Validator::validate($input, self::RULES, [
            'department_id' => 'Department', 'code' => 'Course code', 'name' => 'Course name',
        ]);
        if (!isset($errors['department_id']) && !Department::find((int) $input['department_id'])) {
            $errors['department_id'] = 'Please choose a valid department.';
        }
        if (!isset($errors['code']) && Course::codeExists($input['code'], $exceptId)) {
            $errors['code'] = 'This course code is already in use.';
        }
        return $errors;
    }
}
