<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\UpdateCertificationRequest;
use App\Models\Certification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CertificationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Certification::class);

        return view('admin.certifications.index', [
            'certifications' => Certification::query()->withCount('matieresPremieres')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Certification::class);

        return view('admin.certifications.create');
    }

    public function store(StoreCertificationRequest $request): RedirectResponse
    {
        Certification::create($request->validated());

        return to_route('admin.certifications.index')->with('status', 'Certification créée.');
    }

    public function show(Certification $certification): View
    {
        $this->authorize('view', $certification);

        return view('admin.certifications.show', compact('certification'));
    }

    public function edit(Certification $certification): View
    {
        $this->authorize('update', $certification);

        return view('admin.certifications.edit', compact('certification'));
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): RedirectResponse
    {
        $certification->update($request->validated());

        return to_route('admin.certifications.index')->with('status', 'Certification modifiée.');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $this->authorize('delete', $certification);
        $certification->delete();

        return to_route('admin.certifications.index')->with('status', 'Certification supprimée.');
    }
}