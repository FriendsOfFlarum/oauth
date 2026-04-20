import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Icon from 'flarum/common/components/Icon';
import Badge from 'flarum/common/components/Badge';
import ItemList from 'flarum/common/utils/ItemList';
import type Group from 'flarum/common/models/Group';
import type Mithril from 'mithril';

export default class AuthSettingsPage extends ExtensionPage {
  showing: Record<string, boolean> = {};

  oninit(vnode: Mithril.Vnode<this['attrs'], this>) {
    super.oninit(vnode);

    this.showing = {};
  }

  content() {
    return (
      <div className="container">
        <div className="AuthSettingsPage">
          <form className="Form">
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
              setting: 'fof-oauth.disable_avatars',
              label: app.translator.trans('fof-oauth.admin.settings.disable_avatars_label'),
              help: app.translator.trans('fof-oauth.admin.settings.disable_avatars_help'),
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
          </form>
        </div>
      </div>
    );
  }

  providerSettingsItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    app.data['fof-oauth'].forEach((provider) => {
      const { name } = provider;
      const enabled = !!Number(this.setting(`fof-oauth.${name}`)());
      const showSettings = !!this.showing[name];
      const callbackUrl = `${app.forum.attribute<string>('baseUrl')}/auth/${name}`;

      const groupId = this.setting(`fof-oauth.${name}.group`)();
      const selectedGroup = groupId ? app.store.getById<Group>('groups', groupId) : null;

      items.add(
        `fof-oauth.${name}`,
        <div className={`Provider ${enabled ? 'enabled' : 'disabled'} ${showSettings && 'showing'}`}>
          <div className={`Provider--info Provider--${name}`}>
            {this.buildSettingComponent({
              type: 'boolean',
              setting: `fof-oauth.${name}`,
              label: (
                <div>
                  <Icon name={provider.icon} />
                  <span>{app.translator.trans(`fof-oauth.lib.providers.${name}`)}</span>
                </div>
              ),
            })}

            {enabled && selectedGroup && (
              <div className="Provider--group">
                <Badge icon={selectedGroup.icon() || 'fas fa-user-group'} />
                {selectedGroup.namePlural()}
              </div>
            )}

            <Button
              className={`Button Button--rounded ${this.showing[name] && 'active'}`}
              onclick={() => (this.showing[name] = !showSettings)}
              aria-label={app.translator.trans('fof-oauth.admin.settings_accessibility_label', {
                name,
              })}
            >
              <Icon name="fas fa-cog" />
            </Button>
          </div>

          <div className="Provider--settings" inert={!showSettings}>
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

            <div className="Form">
              {Object.keys(provider.fields).map((field) =>
                this.buildSettingComponent({
                  type: 'string',
                  setting: `fof-oauth.${name}.${field}`,
                  label: app.translator.trans(`fof-oauth.admin.settings.providers.${name}.${field}_label`),
                  required: {
                    [showSettings && provider.fields[field].includes('required') ? 'required' : (null as unknown as string)]: true,
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

  getAvailableGroups(): Group[] {
    const groups = app.store.all<Group>('groups');
    return groups.filter((group) => !['2', '3'].includes(group.id()!)); // Exclude the "Guests" and "Members" groups
  }

  customProviderSettings(name: string): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    // Add group selection dropdown
    items.add(
      'group',
      <div className="Form-group">
        <label>{app.translator.trans('fof-oauth.admin.settings.providers.group_label')}</label>
        <div className="helpText">{app.translator.trans('fof-oauth.admin.settings.providers.group_help')}</div>

        {(() => {
          const groupId = this.setting(`fof-oauth.${name}.group`)();
          const selectedGroup = groupId ? app.store.getById<Group>('groups', groupId) : null;
          const icons: Record<string, string> = {
            1: 'fas fa-check', // Admins
            3: 'fas fa-user', // Members
            4: 'fas fa-map-pin', // Mods
          };

          return (
            <Dropdown
              label={
                selectedGroup
                  ? [<Icon name={selectedGroup.icon() || icons[selectedGroup.id()!]} />, '\t', selectedGroup.namePlural()]
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
                  icon={group.icon() || icons[group.id()!]}
                  onclick={() => this.setting(`fof-oauth.${name}.group`)(group.id()!)}
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
