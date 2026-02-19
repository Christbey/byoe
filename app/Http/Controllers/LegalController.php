<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Shared legal metadata passed to every legal page.
     *
     * @return array{company: string, email: string, state: string, effectiveDate: string}
     */
    private function legalProps(): array
    {
        return [
            'company' => config('legal.company_name'),
            'email' => config('legal.company_email'),
            'state' => config('legal.governing_state'),
            'effectiveDate' => config('legal.effective_date'),
        ];
    }

    public function terms(): Response
    {
        return Inertia::render('legal/Terms', $this->legalProps());
    }

    public function privacy(): Response
    {
        return Inertia::render('legal/Privacy', $this->legalProps());
    }

    public function cookies(): Response
    {
        return Inertia::render('legal/Cookies', $this->legalProps());
    }

    public function contractor(): Response
    {
        return Inertia::render('legal/Contractor', $this->legalProps());
    }
}
