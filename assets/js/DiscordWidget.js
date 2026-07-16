/**
 * Discord widget erişilebilirlik kontrolü.
 *
 * Widget JSON endpoint'ine bir istek atar. Başarısız olursa
 * (Discord'a erişilemiyorsa, ağ tarafından engelleniyorsa vb.)
 * #discordWidget içeriğini "Discord'a bağlanılamadı" mesajı ve
 * bir "Join Server" butonu ile değiştirir.
 *
 * Kullanım:
 * <script
 *   src="/assets/js/discord-widget-check.js"
 *   data-guild-id="GUILD_ID"
 *   data-invite-url="https://discord.gg/xxxxxxx"
 *   data-timeout="6000"
 * ></script>
 */
(function () {
    var currentScript = document.currentScript;
    var guildId = currentScript ? currentScript.getAttribute('data-guild-id') : null;
    var inviteUrl = currentScript ? currentScript.getAttribute('data-invite-url') : '#';
    var timeoutMs = currentScript && currentScript.getAttribute('data-timeout')
        ? parseInt(currentScript.getAttribute('data-timeout'), 10)
        : 6000;

    if (!guildId) {
        return;
    }

    function showFallback(discordWidget) {
        if (!discordWidget) {
            return;
        }

        discordWidget.innerHTML =
            '<div class="discord-fallback">' +
            '<p>Could not connect to Discord.</p>' +
            '<a class="btn-join-discord" href="' + inviteUrl + '" target="_blank" rel="noopener noreferrer">Join Server</a>' +
            '</div>';
    }

    function run() {
        var discordWidget = document.getElementById('discordWidget');

        var controller = new AbortController();
        var timeoutId = setTimeout(function () {
            controller.abort();
        }, timeoutMs);

        fetch('https://discord.com/api/guilds/' + guildId + '/widget.json', {
            mode: 'cors',
            signal: controller.signal
        })
            .then(function (res) {
                clearTimeout(timeoutId);
                if (!res.ok) {
                    throw new Error('Discord widget unavailable');
                }
            })
            .catch(function () {
                clearTimeout(timeoutId);
                showFallback(discordWidget);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();