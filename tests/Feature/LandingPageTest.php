<?php

declare(strict_types=1);

it('renders the DocuPharma landing page', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Quality work,')
        ->assertSee('finally in control.')
        ->assertSee('Document control')
        ->assertSee('Quality management')
        ->assertSee('Responsible AI')
        ->assertSee(url('/admin'));
});
