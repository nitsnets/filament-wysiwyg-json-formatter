<?php

namespace Nitsnets\FilamentWysiwygJsonFormatter\Forms\Components;

use Closure;
use Filament\Forms\Components\Textarea;

class WysiwygEditor extends Textarea
{
    protected string $view = 'filament-wysiwyg-json-formatter::forms.components.wysiwyg-editor';

    protected array|Closure $toolbarButtons = [
        'bold',
        'italic',
        'underline',
        'strike',
        'code',
        'codeBlock',
        'link',
        'unorderedList',
        'orderedList',
        'checklist',
        'divider',
        'h1',
        'h2',
        'h3',
        'blockquote',
        'textColor',
    ];

    protected bool $enableMentions = false;

    protected array|Closure $mentions = [];

    public function toolbarButtons(array|Closure $buttons): static
    {
        $this->toolbarButtons = $buttons;

        return $this;
    }

    public function getToolbarButtons(): array
    {
        return $this->evaluate($this->toolbarButtons);
    }

    public function mentions(array|Closure $mentions): static
    {
        $this->mentions = $mentions;
        $this->enableMentions = true;

        return $this;
    }

    public function getMentions(): array
    {
        return $this->evaluate($this->mentions);
    }

    public function hasMentions(): bool
    {
        return $this->enableMentions;
    }
}

