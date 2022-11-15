import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import LinkButton from 'flarum/common/components/LinkButton';

/**
 * The `ConfigureWithOAuthPage` component is meant for 3rd party extensions to provide a handy link to `fof/oauth` settings.
 * It is not used directly by `fof/oauth` itself.
 */
export default class ConfigureWithOAuthPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
  }

  content() {
    return [
      <div className="container">
        <div className="OAuthSettingsPage">
          <br />
          <LinkButton className="Button" href={app.route('extension', { id: 'fof-oauth' })}>
            {app.translator.trans('fof-oauth.admin.configure_button_label')}
          </LinkButton>
        </div>
      </div>,
    ];
  }
}
