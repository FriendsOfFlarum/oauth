import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import icon from 'flarum/common/helpers/icon';
import ItemList from 'flarum/common/utils/ItemList';

export default class AuthSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.showing = [];
  }

  content() {
    return (
      <div className="container">
        <div className="AuthSettingsPage">
          <div className="Form">
            {this.buildSettingComponent({
              type: 'boolean',
              setting: 'fof-oauth.only_icons',
              label: app.translator.trans(`fof-oauth.admin.settings.only_icons_label`),
            })}
            {this.buildSettingComponent({
              type: 'boolean',
              setting: 'fof-oauth.update_email_from_provider',
              label: app.translator.trans('fof-oauth.admin.settings.update_email_from_provider_label'),
              help: app.translator.trans('fof-oauth.admin.settings.update_email_from_provider_help'),
            })}
            {this.buildSettingComponent({
              type: 'boolean',
              setting: 'fof-oauth.fullscreenPopup',
              label: app.translator.trans('fof-oauth.admin.settings.fullscreen_popup_label'),
              help: app.translator.trans('fof-oauth.admin.settings.fullscreen_popup_help'),
            })}
            {this.buildSettingComponent({
              type: 'number',
              setting: 'fof-oauth.popupWidth',
              label: app.translator.trans('fof-oauth.admin.settings.popup_width_label'),
              help: app.translator.trans('fof-oauth.admin.settings.popup_width_help'),
              placeholder: 580,
              min: 0,
            })}
            {this.buildSettingComponent({
              type: 'number',
              setting: 'fof-oauth.popupHeight',
              label: app.translator.trans('fof-oauth.admin.settings.popup_height_label'),
              help: app.translator.trans('fof-oauth.admin.settings.popup_height_help'),
              placeholder: 400,
              min: 0,
            })}

            <hr />

            {this.providerSettingsItems().toArray()}

            <hr />

            <div className="AuthSettingsPage--advanced">
              <h4>{app.translator.trans('fof-oauth.admin.settings.advanced.heading')}</h4>
              {this.buildSettingComponent({
                type: 'boolean',
                setting: 'fof-oauth.log-oauth-errors',
                label: app.translator.trans('fof-oauth.admin.settings.advanced.log-oauth-errors-label'),
                help: app.translator.trans('fof-oauth.admin.settings.advanced.log-oauth-errors-help'),
              })}
            </div>

            {this.submitButton()}
          </div>
        </div>
      </div>
    );
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

  getAvailableGroups() {
    const groups = app.store.all('groups');
    return groups.filter((group) => !['2', '3'].includes(group.id())); // Exclude the "Guests" and "Members" groups
  }

  customProviderSettings(name) {
    const items = new ItemList();

    // Add group selection dropdown
    items.add(
      'group',
      <div className="Form-group">
        <label>{app.translator.trans('fof-oauth.admin.settings.providers.group_label')}</label>
        <div className="helpText">{app.translator.trans('fof-oauth.admin.settings.providers.group_help')}</div>

        {(() => {
          const groupId = this.setting(`fof-oauth.${name}.group`)();
          const selectedGroup = groupId ? app.store.getById('groups', groupId) : null;
          const icons = {
            1: 'fas fa-check', // Admins
            3: 'fas fa-user', // Members
            4: 'fas fa-map-pin', // Mods
          };

          return (
            <Dropdown
              label={
                selectedGroup
                  ? [icon(selectedGroup.icon() || icons[selectedGroup.id()]), '\t', selectedGroup.namePlural()]
                  : app.translator.trans('fof-oauth.admin.settings.providers.no_group_label')
              }
              buttonClassName="Button"
              disabled={!this.setting(`fof-oauth.${name}`)()}
            >
              <Button icon="fas fa-times" onclick={() => this.setting(`fof-oauth.${name}.group`)('')} active={!groupId}>
                {app.translator.trans('fof-oauth.admin.settings.providers.no_group_label')}
              </Button>

              {this.getAvailableGroups().map((group) => (
                <Button
                  icon={group.icon() || icons[group.id()]}
                  onclick={() => this.setting(`fof-oauth.${name}.group`)(group.id())}
                  active={groupId === group.id()}
                  key={group.id()}
                >
                  {group.namePlural()}
                </Button>
              ))}
            </Dropdown>
          );
        })()}
      </div>
    );

    return items;
  }
}
