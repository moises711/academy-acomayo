<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSitioWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('usuarios.gestionar') ?? false;
    }

    /** @return array<string, array<mixed>|string> */
    public function rules(): array
    {
        return [
            'contenido' => ['required', 'array'],
            'contenido.hero' => ['required', 'array'],
            'contenido.hero.eyebrow' => ['required', 'string', 'max:100'],
            'contenido.hero.title' => ['required', 'string', 'max:180'],
            'contenido.hero.description' => ['required', 'string', 'max:500'],
            'contenido.hero.primary' => ['required', 'string', 'max:60'],
            'contenido.hero.secondary' => ['required', 'string', 'max:60'],
            'contenido.hero.visualLabel' => ['required', 'string', 'max:120'],
            'contenido.hero.caption' => ['required', 'string', 'max:180'],
            'contenido.hero.stats' => ['required', 'array', 'size:3'],
            'contenido.hero.stats.*.value' => ['required', 'string', 'max:20'],
            'contenido.hero.stats.*.label' => ['required', 'string', 'max:100'],
            'contenido.admisiones' => ['required', 'array'],
            'contenido.admisiones.fecha_apertura' => ['nullable', 'required_with:contenido.admisiones.fecha_cierre', 'date'],
            'contenido.admisiones.fecha_cierre' => ['nullable', 'required_with:contenido.admisiones.fecha_apertura', 'date', 'after_or_equal:contenido.admisiones.fecha_apertura'],
            'contenido.admisiones.mensaje' => ['nullable', 'string', 'max:180'],
            'contenido.brand' => ['required', 'string', 'max:100'],
            'contenido.programs' => ['required', 'array'],
            'contenido.programs.eyebrow' => ['required', 'string', 'max:100'],
            'contenido.programs.title' => ['required', 'string', 'max:180'],
            'contenido.programs.description' => ['required', 'string', 'max:500'],
            'contenido.programs.inquire' => ['required', 'string', 'max:100'],
            'contenido.programs.fallback' => ['required', 'array', 'size:3'],
            'contenido.programs.fallback.*.name' => ['required', 'string', 'max:100'],
            'contenido.programs.fallback.*.type' => ['required', 'string', 'max:40'],
            'contenido.programs.fallback.*.modality' => ['required', 'string', 'max:40'],
            'contenido.programs.fallback.*.dates' => ['required', 'string', 'max:100'],
            'contenido.contact' => ['required', 'array'],
            'contenido.contact.title' => ['required', 'string', 'max:180'],
            'contenido.contact.description' => ['required', 'string', 'max:500'],
            'contenido.contact.address' => ['required', 'string', 'max:180'],
            'contenido.contact.hours' => ['required', 'string', 'max:180'],
            'contenido.contact.whatsapp' => ['required', 'string', 'max:100'],
            'contenido.contact.whatsappUrl' => ['nullable', 'url', 'max:255'],
            'contenido.contact.phone' => ['required', 'string', 'max:60'],
            'contenido.contact.email' => ['required', 'string', 'max:60'],
            'contenido.contact.publicPhone' => ['nullable', 'string', 'max:30'],
            'contenido.contact.publicEmail' => ['nullable', 'email', 'max:120'],
            'contenido.contact.addressLabel' => ['required', 'string', 'max:60'],
            'contenido.contact.hoursLabel' => ['required', 'string', 'max:60'],
            'contenido.contact.phoneLabel' => ['required', 'string', 'max:60'],
            'contenido.contact.emailLabel' => ['required', 'string', 'max:60'],
            'contenido.methodology' => ['required', 'array'],
            'contenido.methodology.title' => ['required', 'string', 'max:180'],
            'contenido.methodology.description' => ['required', 'string', 'max:500'],
            'contenido.schedule' => ['required', 'array'],
            'contenido.schedule.eyebrow' => ['required', 'string', 'max:100'],
            'contenido.schedule.title' => ['required', 'string', 'max:180'],
            'contenido.schedule.description' => ['required', 'string', 'max:500'],
            'contenido.schedule.options' => ['required', 'array', 'size:3'],
            'contenido.schedule.options.*.title' => ['required', 'string', 'max:100'],
            'contenido.schedule.options.*.time' => ['required', 'string', 'max:180'],
            'contenido.testimonials' => ['required', 'array'],
            'contenido.testimonials.eyebrow' => ['required', 'string', 'max:100'],
            'contenido.testimonials.title' => ['required', 'string', 'max:180'],
            'contenido.testimonials.items' => ['required', 'array', 'size:2'],
            'contenido.testimonials.items.*.quote' => ['required', 'string', 'max:500'],
            'contenido.testimonials.items.*.name' => ['required', 'string', 'max:100'],
            'contenido.testimonials.items.*.detail' => ['required', 'string', 'max:120'],
            'contenido.faq' => ['required', 'array'],
            'contenido.faq.eyebrow' => ['required', 'string', 'max:100'],
            'contenido.faq.title' => ['required', 'string', 'max:180'],
            'contenido.faq.items' => ['required', 'array', 'size:4'],
            'contenido.faq.items.*.question' => ['required', 'string', 'max:180'],
            'contenido.faq.items.*.answer' => ['required', 'string', 'max:500'],
            'contenido.faculty' => ['required', 'array'],
            'contenido.faculty.title' => ['required', 'string', 'max:180'],
            'contenido.faculty.description' => ['required', 'string', 'max:500'],
            'contenido.results' => ['required', 'array'],
            'contenido.results.title' => ['required', 'string', 'max:180'],
            'contenido.results.description' => ['required', 'string', 'max:500'],
            'contenido.results.metrics' => ['required', 'array', 'size:3'],
            'contenido.results.metrics.*.value' => ['required', 'string', 'max:30'],
            'contenido.results.metrics.*.label' => ['required', 'string', 'max:100'],
            'contenido.results.note' => ['nullable', 'string', 'max:300'],
            'contenido.footer' => ['required', 'array'],
            'contenido.footer.description' => ['required', 'string', 'max:300'],
            'contenido.footer.copyright' => ['required', 'string', 'max:180'],
            'docentes' => ['present', 'array', 'max:30'],
            'docentes.*.id_profesor' => ['required', 'integer', 'distinct', 'exists:profesores,id_profesor'],
            'docentes.*.bio' => ['nullable', 'string', 'max:300'],
            'fotos_docentes' => ['sometimes', 'array', 'max:4'],
            'fotos_docentes.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'foto_portada' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
