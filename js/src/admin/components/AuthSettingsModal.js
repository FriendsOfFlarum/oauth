import { settings } from '@fof-components';

import Button from 'flarum/components/Button';
import SettingsModal from 'flarum/components/SettingsModal';
import icon from 'flarum/helpers/icon';

const {
    items: { BooleanItem, StringItem },
} = settings;

export default class AuthSettingsModal extends SettingsModal {
    oninit(vnode) {
        super.oninit(vnode);

        this.showing = [];

        this.setting = this.setting.bind(this);
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
                <BooleanItem name="fof-oauth.only_icons" setting={this.setting}>
                    {app.translator.trans(`fof-oauth.admin.settings.only_icons_label`)}
                </BooleanItem>
            </div>,

            <hr />,

            app.data['fof-oauth'].map((provider) => {
                const { name } = provider;
                const enabled = !!Number(this.setting(`fof-oauth.${name}`)());
                const showSettings = !!this.showing[name];
                const callbackUrl = `${app.forum.attribute('baseUrl')}/auth/${name}`;

                return (
                    <div className={`Provider ${enabled ? 'enabled' : 'disabled'} ${showSettings && 'showing'}`}>
                        <div className="Provider--info">
                            <div className="Form-group">
                                <BooleanItem name={`fof-oauth.${name}`} setting={this.setting}>
                                    {icon(provider.icon)}
                                    <span>{app.translator.trans(`fof-oauth.lib.providers.${name}`)}</span>
                                </BooleanItem>
                            </div>

                            {
                                <Button
                                    className={`Button Button--rounded ${this.showing[name] && 'active'}`}
                                    onclick={() => (this.showing[name] = !showSettings)}
                                >
                                    {icon(`fas fa-cog`)}
                                </Button>
                            }
                        </div>

                        <div className="Provider--settings">
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
                                        name={`fof-oauth.${name}.${field}`}
                                        setting={this.setting}
                                        {...{ [showSettings && provider.fields[field].includes('required') ? 'required' : null]: true }}
                                    >
                                        {app.translator.trans(`fof-oauth.admin.settings.providers.${name}.${field}_label`)}
                                    </StringItem>
                                ))}
                            </div>
                        </div>
                    </div>
                );
            }),
        ];
    }
}
