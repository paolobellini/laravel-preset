<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\CreateVexDocument;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('creates the vex document identified after the composer package', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['name' => 'acme/app']));

    expect(app(CreateVexDocument::class)->handle(force: false))->toBe(Outcome::Created);

    $document = json_decode(file_get_contents($this->appBase.'/.vex/openvex.json'), true);

    expect($document['@id'])->toBe('urn:vex:acme:app')
        ->and($document['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/');
});

it('falls back to the directory name without a composer name', function () {
    app(CreateVexDocument::class)->handle(force: false);

    expect(json_decode(file_get_contents($this->appBase.'/.vex/openvex.json'), true)['@id'])
        ->toBe('urn:vex:'.basename($this->appBase));
});

it('leaves an existing vex document alone', function () {
    mkdir($this->appBase.'/.vex');
    file_put_contents($this->appBase.'/.vex/openvex.json', '{}');

    expect(app(CreateVexDocument::class)->handle(force: false))->toBe(Outcome::Skipped)
        ->and(file_get_contents($this->appBase.'/.vex/openvex.json'))->toBe('{}');
});
