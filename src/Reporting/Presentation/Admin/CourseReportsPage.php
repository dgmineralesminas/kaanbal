<?php

declare(strict_types=1);

namespace Kaanbal\Reporting\Presentation\Admin;

use Kaanbal\Reporting\Application\CourseReportingQuery;

final class CourseReportsPage
{
    public const CAPABILITY = 'manage_options';

    private const PAGE_SLUG = 'kaanbal-course-reports';

    public function __construct(private readonly CourseReportingQuery $reports)
    {
    }

    public function register(): void
    {
        add_submenu_page(
            'edit.php?post_type=kaanbal_course',
            __('Reportes de cursos', 'kaanbal'),
            __('Reportes', 'kaanbal'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            array($this, 'render'),
        );
    }

    public function canAccess(): bool
    {
        return current_user_can(self::CAPABILITY);
    }

    public function render(): void
    {
        if (! $this->canAccess()) {
            wp_die(esc_html__('No tienes permisos para consultar estos reportes.', 'kaanbal'), '', array('response' => 403));
        }

        $course_id = absint($this->requestValue('course_id'));

        echo '<div class="wrap"><h1>' . esc_html__('Reportes de cursos', 'kaanbal') . '</h1>';

        if ($course_id > 0) {
            $this->renderDetail($course_id);
        } else {
            $this->renderSummary();
        }

        echo '</div>';
    }

    private function renderSummary(): void
    {
        $summary = $this->reports->summary();

        if (array() === $summary['courses']) {
            echo '<p>' . esc_html__('No hay cursos publicados para reportar.', 'kaanbal') . '</p>';

            return;
        }

        echo '<table class="widefat fixed striped"><thead><tr>';
        foreach (array('Curso', 'Alumnos', 'En curso', 'Aprobados', 'Revocados', 'Progreso promedio', 'Tasa de aprobación', 'Acción') as $heading) {
            echo '<th scope="col">' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($summary['courses'] as $course) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $course['title']) . '</td>';
            echo '<td>' . esc_html((string) $course['total']) . '</td>';
            echo '<td>' . esc_html((string) $course['active']) . '</td>';
            echo '<td>' . esc_html((string) $course['completed']) . '</td>';
            echo '<td>' . esc_html((string) $course['revoked']) . '</td>';
            echo '<td>' . esc_html((string) $course['average_progress']) . '%</td>';
            echo '<td>' . esc_html((string) $course['approval_rate']) . '%</td>';
            echo '<td><a class="button" href="' . esc_url($this->url(array('course_id' => (int) $course['course_id']))) . '">' . esc_html__('Ver alumnos', 'kaanbal') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function renderDetail(int $course_id): void
    {
        $detail = $this->reports->detail(
            $course_id,
            max(1, absint($this->requestValue('paged'))),
            $this->requestValue('search'),
            $this->requestValue('status') ?: 'all',
            $this->requestValue('quiz') ?: 'all',
        );

        if (! is_array($detail)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('El curso solicitado no existe o no está disponible para reportes.', 'kaanbal') . '</p></div>';
            echo '<p><a class="button" href="' . esc_url($this->url()) . '">' . esc_html__('Volver a reportes', 'kaanbal') . '</a></p>';

            return;
        }

        echo '<p><a href="' . esc_url($this->url()) . '">&larr; ' . esc_html__('Todos los cursos', 'kaanbal') . '</a></p>';
        echo '<h2>' . esc_html($detail['course']->post_title) . '</h2>';

        if ($detail['certificate_enabled']) {
            echo '<p>' . esc_html__('Este curso contempla certificado externo. Kaanbal no registra su entrega.', 'kaanbal') . '</p>';
        }

        $this->renderFilters($course_id, $detail['filters']);

        if (array() === $detail['students']) {
            echo '<p>' . esc_html__('No hay alumnos que coincidan con los filtros.', 'kaanbal') . '</p>';

            return;
        }

        echo '<p>' . esc_html(sprintf(_n('%d alumno', '%d alumnos', $detail['total'], 'kaanbal'), $detail['total'])) . '</p>';
        echo '<table class="widefat fixed striped"><thead><tr>';
        foreach (array('Alumno', 'Email', 'Estado', 'Progreso', 'Quiz', 'Intentos', 'Fecha de aprobación') as $heading) {
            echo '<th scope="col">' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($detail['students'] as $student) {
            $attempts = null === $student['attempts_used']
                ? '—'
                : (string) $student['attempts_used'] . ' / ' . (null === $student['max_attempts'] ? '—' : (string) $student['max_attempts']);
            $progress = (string) $student['completed_lessons'] . ' / ' . (string) $student['total_lessons'] . ' (' . (string) $student['progress_percentage'] . '%)';
            echo '<tr>';
            echo '<td>' . esc_html((string) $student['name']) . '</td>';
            echo '<td>' . esc_html((string) ($student['email'] ?? '—')) . '</td>';
            echo '<td>' . esc_html((string) $student['status_label']) . '</td>';
            echo '<td>' . esc_html($progress) . '</td>';
            echo '<td>' . esc_html((string) $student['quiz_label']) . '</td>';
            echo '<td>' . esc_html($attempts) . '</td>';
            echo '<td>' . esc_html($this->formatDate($student['completed_at'] ?? null)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        $this->renderPagination($course_id, $detail);
    }

    /** @param array{search: string, status: string, quiz: string} $filters */
    private function renderFilters(int $course_id, array $filters): void
    {
        echo '<form method="get"><input type="hidden" name="post_type" value="kaanbal_course" />';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '" />';
        echo '<input type="hidden" name="course_id" value="' . esc_attr((string) $course_id) . '" />';
        echo '<p><label for="kaanbal-report-search">' . esc_html__('Buscar alumno', 'kaanbal') . '</label> ';
        echo '<input id="kaanbal-report-search" name="search" value="' . esc_attr($filters['search']) . '" /></p>';
        echo '<p><label for="kaanbal-report-status">' . esc_html__('Estado', 'kaanbal') . '</label> ';
        echo '<select id="kaanbal-report-status" name="status">';
        $this->renderOptions(array('all' => 'Todos', 'active' => 'En curso', 'completed' => 'Aprobados', 'revoked' => 'Revocados'), $filters['status']);
        echo '</select> <label for="kaanbal-report-quiz">' . esc_html__('Quiz', 'kaanbal') . '</label> ';
        echo '<select id="kaanbal-report-quiz" name="quiz">';
        $this->renderOptions(array('all' => 'Todos', 'passed' => 'Aprobado', 'not_passed' => 'No aprobado'), $filters['quiz']);
        echo '</select> <button class="button">' . esc_html__('Filtrar', 'kaanbal') . '</button></p></form>';
    }

    /** @param array<string, string> $options */
    private function renderOptions(array $options, string $selected): void
    {
        foreach ($options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
        }
    }

    /** @param array{page: int, total_pages: int, filters: array{search: string, status: string, quiz: string}} $detail */
    private function renderPagination(int $course_id, array $detail): void
    {
        if ($detail['total_pages'] <= 1) {
            return;
        }

        echo '<p class="tablenav-pages">';

        for ($page = 1; $page <= $detail['total_pages']; $page++) {
            if ($page === $detail['page']) {
                echo ' <strong>' . esc_html((string) $page) . '</strong> ';
                continue;
            }

            echo ' <a href="' . esc_url($this->url(array_merge(array('course_id' => $course_id, 'paged' => $page), $detail['filters']))) . '">' . esc_html((string) $page) . '</a> ';
        }

        echo '</p>';
    }

    /** @param array<string, int|string> $parameters */
    private function url(array $parameters = array()): string
    {
        $parameters = array_map(static fn (int|string $value): string => rawurlencode((string) $value), $parameters);

        return add_query_arg(array_merge(array('post_type' => 'kaanbal_course', 'page' => self::PAGE_SLUG), $parameters), admin_url('edit.php'));
    }

    private function formatDate(?string $date): string
    {
        if (null === $date || '' === $date) {
            return '—';
        }

        $timestamp = strtotime($date . ' UTC');

        if (false === $timestamp) {
            return '—';
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }

    private function requestValue(string $key): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters do not change state.
        if (! isset($_GET[$key]) || ! is_scalar($_GET[$key])) {
            return '';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters do not change state.
        return sanitize_text_field(wp_unslash((string) $_GET[$key]));
    }
}
