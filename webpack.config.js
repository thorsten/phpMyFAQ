const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const isDevelopment = process.env.NODE_ENV === 'development';

module.exports = {
  entry: {
    vendors: './phpmyfaq/assets/src/vendors.js',
    frontend: './phpmyfaq/assets/src/frontend.js',
    backend: './phpmyfaq/admin/assets/src/index.js',
  },
  plugins: [
    new CleanWebpackPlugin(),
    new MiniCssExtractPlugin({
      filename: isDevelopment ? '[name].css' : '[name].[hash].css',
      chunkFilename: isDevelopment ? '[id].css' : '[id].[hash].css',
    }),
  ],
  output: {
    filename: '[name].bundle.js',
    path: path.resolve(__dirname, 'phpmyfaq/assets/dist'),
  },
  mode: 'production',
  resolve: {
    extensions: ['.js', '.jsx', '.scss'],
  },
};
