import Encore from '@symfony/webpack-encore';

// Necessário quando uma ferramenta lê este arquivo sem passar pelo comando "encore".
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    // Uma entrada só: o runtime do webpack vai dentro do app.js, sem um runtime.js à parte.
    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // Em produção, nomes com hash (app.3f2a1c.css): o navegador nunca usa um arquivo velho do cache.
    .enableVersioning(Encore.isProduction())
    .enableSassLoader((options) => {
        options.sassOptions = {
            // O Bootstrap 5.3 usa @import e funções que o Sass atual marca como deprecated. São avisos do
            // código dele (quietDeps) e do nosso @import dele, que só some com o Bootstrap 6.
            quietDeps: true,
            silenceDeprecations: ['import'],
        };
    })
    // No Docker Desktop do Windows, eventos de arquivo do bind mount não chegam ao container (ADR 0011).
    // Com polling, o "npm run watch" percebe as alterações mesmo assim.
    .configureWatchOptions((options) => {
        options.poll = 1000;
        options.ignored = /node_modules/;
    });

export default await Encore.getWebpackConfig();
