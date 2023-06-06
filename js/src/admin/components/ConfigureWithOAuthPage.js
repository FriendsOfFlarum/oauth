import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ConfigureWithOAuthButton from './ConfigureWithOAuthButton';

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
          <ConfigureWithOAuthButton />
        </div>
      </div>,
    ];
  }
}
