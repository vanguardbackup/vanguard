import './bootstrap';
import '../../vendor/masmerise/livewire-toaster/resources/js';

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css'; // optional for styling
window.tippy = tippy;

function prefersDarkMode() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
}
function printVanguardLogo() {
    const color = prefersDarkMode() ? '#FFFFFF' : '#000000';
    const styles = [
        `color: ${color}`,
        'font-size: 12px',
        'font-weight: bold',
        'text-shadow: 1px 1px 0 rgb(128,128,128,0.3)',
        'padding: 10px 0',
    ].join(';');

    console.log(
        '%c' +
            [
                ' _    __                                     __',
                '| |  / /___ _____  ____ ___  ____ ___ ______  / /',
                '| | / / __ `/ __ \\/ __ `/ / / / __ `/ ___/ / / ',
                '| |/ / /_/ / / / / /_/ / /_/ / /_/ / /  _ / /  ',
                '|___/\\__,_/_/ /_/\\__, /\\__,_/\\__,_/_/  (_)_/   ',
                '                /____/                         ',
            ].join('\n'),
        styles,
    );
}

function printGitHubLink() {
    const color = prefersDarkMode() ? '#FFFFFF' : '#000000';
    const linkColor = prefersDarkMode() ? '#00FFFF' : '#0000FF';

    console.log(
        '%cCheck out the project on GitHub: %chttps://github.com/vanguardbackup/vanguard\n%cThanks for using Vanguard! We appreciate your support.',
        `color: ${color}; font-weight: bold;`,
        `color: ${linkColor}; font-weight: normal; text-decoration: underline;`,
        `color: ${color}; font-weight: normal;`,
    );
}
function printConsoleMessages() {
    printVanguardLogo();
    printGitHubLink();
}
document.addEventListener('livewire:init', () => {
    printConsoleMessages();
    Livewire.hook('commit', ({ component, commit, succeed }) => {
        succeed(({ effects }) => {
            if (effects.path) {
                printConsoleMessages();
            }
        });
    });
});
