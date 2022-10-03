const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const ConcatPlugin = require('@mcler/webpack-concat-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    backend: './phpmyfaq/admin/assets/src/index.js',
    frontend: './phpmyfaq/assets/src/frontend.js',
    setup: './phpmyfaq/assets/src/setup.js',
    styles: './phpmyfaq/assets/themes/default/scss/style.scss',
    admin: './phpmyfaq/admin/assets/scss/style.scss',
    //vendors: './phpmyfaq/assets/src/vendors.js', // @todo still needed?
  },
  devtool: 'source-map',
  output: {
    path: path.resolve(__dirname, 'phpmyfaq/assets/dist'),
  },
  mode: 'development',
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
      fileName: '../../../phpmyfaq/admin/assets/js/editor/plugins/phpmyfaq/plugin.min.js',
      filesToConcat: [path.resolve(__dirname, 'phpmyfaq/admin/assets/js/phpmyfaq.tinymce.plugin.js')],
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
  /*

  plugins: [
    new CopyPlugin({
      patterns: [
        {
          from: 'node_modules/tinymce/tinymce.min.js',
          to: path.resolve(__dirname, 'phpmyfaq/admin/assets/js/editor/tinymce.min.js'),
        },
        {
          from: 'node_modules/tinymce/icons/',
          to: path.resolve(__dirname, 'phpmyfaq/admin/assets/js/editor/icons/'),
        },
        {
          from: 'node_modules/tinymce/plugins/',
          to: path.resolve(__dirname, 'phpmyfaq/admin/assets/js/editor/plugins/'),
        },
        {
          from: 'node_modules/tinymce/skins/',
          to: path.resolve(__dirname, 'phpmyfaq/admin/assets/js/editor/skins/'),
        },
        {
          from: 'node_modules/tinymce/themes/',
          to: path.resolve(__dirname, 'phpmyfaq/admin/assets/js/editor/themes/'),
        },
        {
          from: 'node_modules/highlight.js/lib/index.js',
          to: path.resolve(__dirname, 'phpmyfaq/assets/js/libs'),
        },
        {
          from: 'node_modules/highlight.js/styles/default.css',
          to: path.resolve(__dirname, 'phpmyfaq/assets/js/libs'),
        },
      ],
    }),
    new ConcatPlugin({
      fileName: 'phpmyfaq.js',
      filesToConcat: [
        //path.resolve(__dirname, 'phpmyfaq/assets/src/add.js'),
        //path.resolve(__dirname, 'phpmyfaq/assets/src/category.js'),
        //path.resolve(__dirname, 'phpmyfaq/assets/src/comments.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/editor.js'),
        //path.resolve(__dirname, 'phpmyfaq/assets/src/records.js'),
        //path.resolve(__dirname, 'phpmyfaq/assets/src/typeahead.js'),
        //path.resolve(__dirname, 'phpmyfaq/assets/src/functions.js'),
      ],
    }),
    // Concat phpMyFAQ TinyMCE plugin and uglify it
    new ConcatPlugin({
      fileName: '../../../phpmyfaq/admin/assets/js/editor/plugins/phpmyfaq/plugin.min.js',
      filesToConcat: [path.resolve(__dirname, 'phpmyfaq/admin/assets/js/phpmyfaq.tinymce.plugin.js')],
    }),
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css',
    }),
  ],
  optimization: {
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        tinymceVendor: {
          test: /[\\/]node_modules[\\/](tinymce)[\\/](.*js|.*skin.css)|[\\/]plugins[\\/]/,
          name: 'tinymce',
        },
      },
    },
  },
   */
};
