<?php


namespace FoF\OAuth;


use Flarum\Settings\SettingsRepositoryInterface;

abstract class Provider
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    abstract protected function name(): string;

    abstract protected function link(): string;

    abstract protected function package(): string;

    abstract protected function controller(): string;

    abstract protected function fields(): array;

    abstract protected function available(): bool;

    protected function icon(): string {
        return "fab fa-{$this->name()}";
    }

    public function enabled() {
        $enabled = $this->settings->get("fof-oauth.{$this->name()}");

        return $enabled && $this->available();
    }

    public function toForumPayload() {
        return [
            'name' => $this->name(),
            'icon' => $this->icon()
        ];
    }

    public function toAdminPayload() {
        return [
            'name' => $this->name(),
            'icon' => $this->icon(),
            'link' => $this->link(),
            'package' => $this->package(),
            'fields' => $this->fields(),
            'available' => $this->available(),
        ];
    }
}
