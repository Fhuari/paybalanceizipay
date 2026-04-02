let mix = require('laravel-mix');
let webpack = require('webpack');
mix.webpackConfig({
  plugins: [
    new webpack.DefinePlugin({
        __VUE_PROD_DEVTOOLS__: 'false',
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false'
    })
  ]
});
mix.js('src/vue/pearpay.js', 'assets/js').vue()
mix.js('src/admin/admin.js', 'assets/js').vue()
