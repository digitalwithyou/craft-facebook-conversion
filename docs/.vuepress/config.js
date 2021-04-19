module.exports = {
    title: 'Facebook Conversion',
    description: 'Craft CMS plugin to send web events directly to Facebook',
    themeConfig: {
        displayAllHeaders: true,
        sidebar: [
            ['/documentation/', 'Getting Started'],
            '/documentation/auto-tracking',
            '/documentation/manual-tracking',
            '/documentation/debugging',
        ],
        nav: [
            { text: 'Documentation', link: '/documentation/' },
            { text: 'Plugin Store', link: 'https://plugins.craftcms.com/facebook-conversion' },
            { text: 'GitHub', link: 'https://github.com/digitalwithyou/craft-facebook-conversion' },
        ]
    },
    head: [
        ['script', { src: 'https://cdn.panelbear.com/analytics.js?site=3F6WJMN2HHX', async: true }],
        ['script', {}, 'window.panelbear = window.panelbear || function() { (window.panelbear.q = window.panelbear.q || []).push(arguments); }; panelbear(\'config\', { site: \'3F6WJMN2HHX\' });']
    ]
}
