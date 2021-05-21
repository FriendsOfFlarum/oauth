import app from 'flarum/forum/app';

import { extend, override } from 'flarum/common/extend';
import LogInButtons from 'flarum/forum/components/LogInButtons';
import LogInButton from 'flarum/forum/components/LogInButton';
import extractText from 'flarum/common/utils/extractText';
import Tooltip from 'flarum/common/components/Tooltip';

app.initializers.add('fof/oauth', () => {
    const onlyIcons = !!Number(app.data['fof-oauth.only_icons']);

    extend(LogInButtons.prototype, 'items', function (items) {
        app.forum
            .attribute('fof-oauth')
            .filter(Boolean)
            .forEach(({ name, icon }) => {
                let className = `Button FoFLogInButton LogInButton--${name}`;

                // Google branding does not allow inline icon, so we'll keep the full button
                if (onlyIcons && name !== 'google') {
                    className += ' Button--icon';
                }

                items.add(
                    name,
                    <LogInButton className={className} icon={icon} path={`/auth/${name}`}>
                        {app.translator.trans(`fof-oauth.forum.log_in.with_button`, {
                            provider: app.translator.trans(`fof-oauth.lib.providers.${name}`),
                        })}
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
