import Encore from '@symfony/webpack-encore';

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    // o runtime do webpack vai dentro do app.js, sem um runtime.js à parte.
    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableTypeScriptLoader()
    .enableSourceMaps(!Encore.isProduction())
    // em produção, nomes com hash (app.3f2a1c.css), o navegador nunca usa um arquivo velho do cache.
    .enableVersioning(Encore.isProduction())
    .enableSassLoader((options) => {
        options.sassOptions = {
            quietDeps: true,
            silenceDeprecations: ['import'],
        };
    })
    // (ADR 0011).
    // "npm run watch" percebe as alterações.
    .configureWatchOptions((options) => {
        options.poll = 1000;
        options.ignored = /node_modules/;
    });

export default await Encore.getWebpackConfig();
