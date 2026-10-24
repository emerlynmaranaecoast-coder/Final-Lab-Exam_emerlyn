<?php
/** DashboardController - summary of all lists. */
class DashboardController extends Controller
{
    public function index(): void
    {
        $stats = [
            'students'    => Student::count(),
            'departments' => Department::count(),
            'courses'     => Course::count(),
            'records'     => Academic::count(),
        ];
        $byStatus = Student::countByStatus();
        $recent = Student::recent(5);

        $perDepartment = Database::fetchAll(
            'SELECT d.code, d.name, COUNT(DISTINCT a.student_id) AS students
               FROM departments d
               LEFT JOIN courses c ON c.department_id = d.id
               LEFT JOIN student_academics a ON a.course_id = c.id
              GROUP BY d.id, d.code, d.name
              ORDER BY d.name'
        );

        $this->render('dashboard/index', compact('stats', 'byStatus', 'recent', 'perDepartment'));
    }
}
