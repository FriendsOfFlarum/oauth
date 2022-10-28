import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import icon from 'flarum/common/helpers/icon';
import ItemList from 'flarum/common/utils/ItemList';

export default class AuthSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.showing = [];
  }

  content() {
    return [
      <div className="container">
        <div className="AuthSettingsPage">
          <div className="Form">
            {this.buildSettingComponent({
              type: 'boolean',
              setting: 'fof-oauth.only_icons',
              label: app.translator.trans(`fof-oauth.admin.settings.only_icons_label`),
            })}

            <hr />

            {this.providerSettingsItems().toArray()}

            {this.submitButton()}
          </div>
        </div>
      </div>,
    ];
  }

  providerSettingsItems() {
    const items = new ItemList();

    app.data['fof-oauth'].map((provider) => {
      const { name } = provider;
      const enabled = !!Number(this.setting(`fof-oauth.${name}`)());
      const showSettings = !!this.showing[name];
      const callbackUrl = `${app.forum.attribute('baseUrl')}/auth/${name}`;

      items.add(
        `fof-oauth.${name}`,
        <div className={`Provider ${enabled ? 'enabled' : 'disabled'} ${showSettings && 'showing'}`}>
          <div className={`Provider--info Provider--${name}`}>
            {this.buildSettingComponent({
              type: 'boolean',
              setting: `fof-oauth.${name}`,
              label: (
                <div>
                  {icon(provider.icon)}
                  <span>{app.translator.trans(`fof-oauth.lib.providers.${name}`)}</span>
                </div>
              ),
            })}

            {
              <Button
                className={`Button Button--rounded ${this.showing[name] && 'active'}`}
                onclick={() => (this.showing[name] = !showSettings)}
                aria-label={app.translator.trans('fof-oauth.admin.settings_accessibility_label', {
                  name,
                })}
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

              {Object.keys(provider.fields).map((field) =>
                this.buildSettingComponent({
                  type: 'string',
                  setting: `fof-oauth.${name}.${field}`,
                  label: app.translator.trans(`fof-oauth.admin.settings.providers.${name}.${field}_label`),
                  required: {
                    [showSettings && provider.fields[field].includes('required') ? 'required' : null]: true,
                  },
                })
              )}

              {this.customProviderSettings(name).toArray()}
            </div>
          </div>
        </div>
      );
    });

    return items;
  }

  customProviderSettings(name) {
    const items = new ItemList();

    return items;
  }
}
