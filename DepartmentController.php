<?php
/** DepartmentController - add, list, view, update and delete departments. */
class DepartmentController extends Controller
{
    private const RULES = [
        'code'        => 'required|max:20|pattern:/^[A-Za-z0-9._-]+$/',
        'name'        => 'required|max:150',
        'description' => 'max:500',
    ];

    /** List all departments (with search + pagination). */
    public function index(): void
    {
        $q = $this->query('q');
        $result = Department::search($q, $this->page(), (int) Config::get('app.per_page', 10));
        $this->render('departments/index', compact('result', 'q'));
    }

    /** Show the "add department" form. */
    public function create(): void
    {
        $this->render('departments/form', ['department' => null, 'values' => [], 'errors' => []]);
    }

    public function store(): void
    {
        $this->requirePost();
        $input = $this->input();
        $errors = $this->validate($input);

        if ($errors) {
            $this->render('departments/form', ['department' => null, 'values' => $input, 'errors' => $errors]);
            return;
        }
        $id = Department::create($input);
        Logger::info('department.created', ['id' => $id, 'code' => $input['code']]);
        flash('success', 'Department added successfully.');
        $this->redirect('departments/show', ['id' => $id]);
    }

    /** View one department and its courses. */
    public function show(): void
    {
        $department = Department::find($this->id()) ?? $this->notFound('department');
        $courses = Database::fetchAll('SELECT * FROM courses WHERE department_id = ? ORDER BY code', [$department['id']]);
        $this->render('departments/show', compact('department', 'courses'));
    }

    public function edit(): void
    {
        $department = Department::find($this->id()) ?? $this->notFound('department');
        $this->render('departments/form', ['department' => $department, 'values' => $department, 'errors' => []]);
    }

    public function update(): void
    {
        $this->requirePost();
        $department = Department::find($this->id()) ?? $this->notFound('department');
        $input = $this->input();
        $errors = $this->validate($input, (int) $department['id']);

        if ($errors) {
            $this->render('departments/form', ['department' => $department, 'values' => $input, 'errors' => $errors]);
            return;
        }
        Department::update((int) $department['id'], $input);
        Logger::info('department.updated', ['id' => $department['id']]);
        flash('success', 'Department updated successfully.');
        $this->redirect('departments/show', ['id' => $department['id']]);
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->requireRole('admin');
        $department = Department::find($this->id()) ?? $this->notFound('department');

        if (Department::courseCount((int) $department['id']) > 0) {
            flash('danger', 'This department still has courses. Delete or move its courses first.');
            $this->redirect('departments/show', ['id' => $department['id']]);
        }
        Department::delete((int) $department['id']);
        Logger::info('department.deleted', ['id' => $department['id'], 'code' => $department['code']]);
        flash('success', 'Department deleted.');
        $this->redirect('departments/index');
    }

    // ---- helpers ----------------------------------------------------------

    private function input(): array
    {
        return [
            'code'        => strtoupper($this->post('code')),
            'name'        => $this->post('name'),
            'description' => $this->post('description'),
        ];
    }

    private function validate(array $input, int $exceptId = 0): array
    {
        $errors = Validator::validate($input, self::RULES, ['code' => 'Department code', 'name' => 'Department name']);
        if (!isset($errors['code']) && Department::codeExists($input['code'], $exceptId)) {
            $errors['code'] = 'This department code is already in use.';
        }
        return $errors;
    }
}
