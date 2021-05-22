import app from 'flarum/forum/app';

import { extend, override } from 'flarum/common/extend';
import LogInButtons from 'flarum/forum/components/LogInButtons';
import LogInButton from 'flarum/forum/components/LogInButton';
import extractText from 'flarum/common/utils/extractText';
import Tooltip from 'flarum/common/components/Tooltip';

export const translateLogIn = (name) => {
    const KEY = `fof-oauth.forum.log_in.with_${name}_button`;
    const specificTranslation = app.translator.trans(KEY);

    if (specificTranslation !== KEY) return specificTranslation;

    const PROVIDER_KEY = `fof-oauth.forum.providers.${name}`;
    const alternativeProvider = app.translator.trans(PROVIDER_KEY);
    const provider = alternativeProvider !== PROVIDER_KEY ? alternativeProvider : app.translator.trans(`fof-oauth.lib.providers.${name}`);

    return app.translator.trans(`fof-oauth.forum.log_in.with_button`, {
        provider,
    });
};

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
                <LogInButton className={className} icon={icon} path={`/auth/${name}`}>
                    {translateLogIn(name)}
                </LogInButton>
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
});
