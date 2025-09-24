<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Modal')]
class Modal
{
    public string $id = '';
    public string $title = '';
    public string $size = 'md';
    public bool $closable = true;
    public bool $backdrop = true;
    public string $class = '';

    public function mount(
        string $id = '',
        string $title = '',
        string $size = 'md',
        bool $closable = true,
        bool $backdrop = true,
        string $class = ''
    ): void {
        $this->id = $id ?: 'modal-' . uniqid();
        $this->title = $title;
        $this->size = $size;
        $this->closable = $closable;
        $this->backdrop = $backdrop;
        $this->class = $class;
    }

    public function getSizeClasses(): string
    {
        return match ($this->size) {
            'sm' => 'max-w-md',
            'lg' => 'max-w-4xl',
            'xl' => 'max-w-6xl',
            'full' => 'max-w-full mx-4',
            default => 'max-w-2xl',
        };
    }
}
