const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const ConcatPlugin = require('webpack-concat-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    vendors: './phpmyfaq/assets/src/vendors.js',
    frontend: './phpmyfaq/assets/src/frontend.js',
    backend: './phpmyfaq/admin/assets/src/index.js',
    styles: './phpmyfaq/assets/themes/default/scss/style.scss',
    'admin-styles': './phpmyfaq/admin/assets/scss/style.scss',
  },
  plugins: [
    new CleanWebpackPlugin(),
    new CopyPlugin([
      {
        from: 'node_modules/tinymce/tinymce.min.js',
        to: path.resolve(__dirname, 'phpmyfaq/admin/assets/js/editor/tinymce.min.js'),
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
        from: 'node_modules/highlight.js/lib/highlight.js',
        to: path.resolve(__dirname, 'phpmyfaq/assets/js/libs'),
      },
      {
        from: 'node_modules/highlight.js/styles/default.css',
        to: path.resolve(__dirname, 'phpmyfaq/assets/js/libs'),
      },
    ]),
    new ConcatPlugin({
      uglify: true,
      fileName: 'phpmyfaq.js',
      filesToConcat: [
        path.resolve(__dirname, 'phpmyfaq/assets/src/add.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/category.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/comments.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/editor.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/faq.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/records.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/typeahead.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/functions.js'),
        path.resolve(__dirname, 'phpmyfaq/assets/src/setup.js'),
      ],
    }),
    /**
     * Concat TinyMCE plugin and uglify it
     */
    new ConcatPlugin({
      uglify: true,
      outputPath: '../../../',
      fileName: 'phpmyfaq/admin/assets/js/editor/plugins/phpmyfaq/plugin.min.js',
      filesToConcat: [path.resolve(__dirname, 'phpmyfaq/admin/assets/js/phpmyfaq.tinymce.plugin.js')],
    }),
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css',
      filesToConcat: [],
    }),
  ],
  output: {
    path: path.resolve(__dirname, 'phpmyfaq/assets/dist'),
  },
  mode: 'production',
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              publicPath: (resourcePath, context) => {
                return path.relative(path.dirname(resourcePath), context) + '/assets/dist/';
              },
              outputPath: 'css/',
            },
          },
          'css-loader',
          'sass-loader',
        ],
      },
      {
        test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: 'fonts/',
              publicPath: './fonts/',
            },
          },
        ],
      },
    ],
  },
};
