import app from 'flarum/admin/app';
import LinkButton from 'flarum/common/components/LinkButton';

export default class ConfigureWithOAuthButton extends LinkButton {
  view() {
    return [
      <LinkButton className="Button" href={app.route('extension', { id: 'fof-oauth' })}>
        {app.translator.trans('fof-oauth.admin.configure_button_label')}
      </LinkButton>,
    ];
  }
}
