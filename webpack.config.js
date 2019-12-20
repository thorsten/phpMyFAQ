const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = {
  entry: './phpmyfaq/assets/src/index.js',
  plugins: [new CleanWebpackPlugin()],
  output: {
    filename: 'vendor.bundle.js',
    path: path.resolve(__dirname, 'phpmyfaq/assets/dist'),
  },
  mode: 'production',
};
