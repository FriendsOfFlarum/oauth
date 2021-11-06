import app from 'flarum/forum/app';

import { extend, override } from 'flarum/common/extend';
import LogInButtons from 'flarum/forum/components/LogInButtons';
import LogInButton from 'flarum/forum/components/LogInButton';
import extractText from 'flarum/common/utils/extractText';
import Tooltip from 'flarum/common/components/Tooltip';
import SignUpModal from 'flarum/forum/components/SignUpModal';

app.initializers.add('fof/oauth', () => {
    const onlyIcons = !!Number(app.data['fof-oauth.only_icons']);

    extend(LogInButtons.prototype, 'items', function (items) {
        const buttons = app.forum.attribute('fof-oauth').filter(Boolean);
        const googleButton = buttons.splice(buttons.indexOf(buttons.find((b) => b.name === 'google')), 1);

        buttons.concat(googleButton).forEach(({ name, icon }) => {
            let className = `Button FoFLogInButton LogInButton--${name}`;

            // Google branding does not allow inline icon, so we'll keep the full button
            if (onlyIcons && name !== 'google') {
                className += ' Button--icon';
            }

            items.add(
                name,
                <div className={`LogInButtonContainer LogInButtonContainer--${name}`}>
                    <LogInButton className={className} icon={icon} path={`/auth/${name}`}>
                        {app.translator.trans(`fof-oauth.forum.log_in.with_${name}_button`, {
                            provider: app.translator.trans(`fof-oauth.forum.providers.${name}`),
                        })}
                    </LogInButton>
                </div>
            );
        });
    });

    if (onlyIcons) {
        override(LogInButton.prototype, 'view', function (orig, vnode) {
            const child = orig(vnode);

            return <Tooltip text={extractText(child.children)}>{child}</Tooltip>;
        });

        extend(LogInButtons.prototype, 'view', function (vdom) {
            vdom.attrs.className += ' FoFLogInButtons--icons';
        });
    }

    extend(SignUpModal.prototype, 'fields', function (items) {
        // If a suggested username was not provided by the OAuth service, display some help text to the user.
        if (!!this.attrs.token && !!!this.attrs.username) {
            items.add(
                'username-help',
                <div>
                    <p>{app.translator.trans('fof-oauth.forum.signup.username_help')}</p>
                </div>,
                35
            );
        }

        return items;
    });
});
