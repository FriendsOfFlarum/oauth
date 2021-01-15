import { extend, override } from 'flarum/extend';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';
import extractText from 'flarum/utils/extractText';

app.initializers.add('fof/oauth', () => {
    const onlyIcons = !!Number(app.data['fof-oauth.only_icons']);

    extend(LogInButtons.prototype, 'items', function (items) {
        app.forum
            .attribute('fof-oauth')
            .filter(Boolean)
            .forEach(({name, icon}) => {
                let className = `Button FoFLogInButton LogInButton--${name}`;

                if (onlyIcons) {
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
        extend(LogInButton.prototype, 'oncreate', function () {
            this.$().tooltip({ container: '#modal' });
        });

        override(LogInButton.prototype, 'view', function (orig, vnode) {
            this.attrs.title = extractText(vnode.children);

            return orig(vnode);
        });

        extend(LogInButtons.prototype, 'view', function (vdom) {
            vdom.attrs.className += ' FoFLogInButtons--icons';
        });
    }
});
