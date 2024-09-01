const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const ConcatPlugin = require('@mcler/webpack-concat-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    backend: './phpmyfaq/admin/assets/src/index.js',
    frontend: './phpmyfaq/assets/src/frontend.js',
    cookieConsent: './phpmyfaq/assets/src/cookie-consent.js',
    setup: './phpmyfaq/assets/src/setup.js',
    update: './phpmyfaq/assets/src/update.js',
    styles: './phpmyfaq/assets/scss/style.scss',
    admin: './phpmyfaq/admin/assets/scss/style.scss',
    debugMode: './phpmyfaq/assets/scss/debug-mode.scss',
  },
  devtool: 'source-map',
  output: {
    path: path.resolve(__dirname, 'phpmyfaq/assets/dist'),
  },
  mode: 'production',
  watchOptions: {
    aggregateTimeout: 200,
    poll: 1000,
  },
  plugins: [
    new CleanWebpackPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css',
    }),
    // Concat phpMyFAQ TinyMCE plugin and uglify it
    new ConcatPlugin({
      fileName: '../../../phpmyfaq/assets/dist/plugins/phpmyfaq/plugin.js',
      filesToConcat: [path.resolve(__dirname, 'phpmyfaq/admin/assets/src/tinymce/phpmyfaq.tinymce.plugin.js')],
    }),
  ],
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
          },
          'css-loader',
          'sass-loader',
        ],
      },
      {
        test: /\.(jpe?g|svg|png|gif|ico|eot|ttf|woff2?)(\?v=\d+\.\d+\.\d+)?$/i,
        type: 'asset/resource',
      },
      {
        test: /\.css$/i,
        use: ['style-loader', 'css-loader'],
      },
    ],
  },
};
