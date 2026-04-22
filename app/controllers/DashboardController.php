<?php

declare(strict_types=1);

class DashboardController extends Controller
{
    public function __construct(
        array $request = [],
        private readonly DashboardService $dashboard = new DashboardService()
    ) {
        parent::__construct($request);
    }

    public function index(): string
    {
        return $this->render('dashboard/index', $this->dashboard->overview());
    }
}
