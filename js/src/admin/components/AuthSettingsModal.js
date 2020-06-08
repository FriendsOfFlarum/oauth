import { settings } from '@fof-components';

import Button from 'flarum/components/Button';
import SettingsModal from 'flarum/components/SettingsModal';
import icon from 'flarum/helpers/icon';

const {
    items: { BooleanItem, StringItem },
} = settings;

export default class AuthSettingsModal extends SettingsModal {
    init() {
        super.init();

        this.showing = [];
    }

    title() {
        return app.translator.trans('fof-oauth.admin.settings.title');
    }

    className() {
        return 'AuthSettingsModal Modal Modal--medium';
    }

    form() {
        return [
            <div className="Form-group">
                <BooleanItem key="fof-oauth.only_icons">{app.translator.trans(`fof-oauth.admin.settings.only_icons_label`)}</BooleanItem>
            </div>,

            <hr />,

            app.data['fof-oauth'].map((provider) => {
                const { name, available } = provider;
                const enabled = !!Number(this.setting(`fof-oauth.${name}`)());
                const showSettings = !!this.showing[name];
                const callbackUrl = `${app.forum.attribute('baseUrl')}/auth/${name}`;

                return (
                    <div
                        className={`Provider ${enabled ? 'enabled' : 'disabled'} ${available ? 'available' : 'unavailable'} ${
                            showSettings && 'showing'
                        }`}
                    >
                        <div className="Provider--info">
                            <div className="Form-group">
                                <BooleanItem key={`fof-oauth.${name}`}>
                                    {icon(provider.icon)}
                                    <span>{app.translator.trans(`fof-oauth.lib.providers.${name}`)}</span>
                                </BooleanItem>
                            </div>

                            {
                                <Button
                                    className={`Button Button--rounded ${this.showing[name] && 'active'}`}
                                    onclick={() => (this.showing[name] = !showSettings)}
                                >
                                    {icon(`fas fa-${available ? 'cog' : 'question'}`)}
                                </Button>
                            }
                        </div>

                        <div className="Provider--settings">
                            {available ? (
                                <div>
                                    <p>
                                        {app.translator.trans(`fof-oauth.admin.settings.providers.${name}.description`, {
                                            link: (
                                                <a href={provider.link} target="_blank">
                                                    {provider.link}
                                                </a>
                                            ),
                                        })}
                                    </p>
                                    <p>
                                        {app.translator.trans(`fof-oauth.admin.settings.providers.callback_url_text`, {
                                            url: (
                                                <a href={callbackUrl} target="_blank">
                                                    {callbackUrl}
                                                </a>
                                            ),
                                        })}
                                    </p>

                                    {Object.keys(provider.fields).map((field) => (
                                        <StringItem
                                            key={`fof-oauth.${name}.${field}`}
                                            {...{ [showSettings && provider.fields[field].includes('required') ? 'required' : '']: true }}
                                        >
                                            {app.translator.trans(`fof-oauth.admin.settings.providers.${name}.${field}_label`)}
                                        </StringItem>
                                    ))}
                                </div>
                            ) : (
                                <p>
                                    {app.translator.trans('fof-oauth.admin.settings.providers.requires_package_text', { package: provider.package })}
                                </p>
                            )}
                        </div>
                    </div>
                );
            }),
        ];
    }
}
