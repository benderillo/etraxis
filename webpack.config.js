const glob = require('glob');
const path = require('path');

module.exports = {

    mode: 'development',

    entry: () => {
        let files = {};

        for (let entry of glob.sync('./templates/**/*.js')) {
            let name = entry.substr('./templates'.length);
            files[name] = entry;
        }

        return files;
    },

    output: {
        path: path.resolve(__dirname, './public/js'),
        filename: '[name]',
    },

    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: 'babel-loader',
            },
        ],
    },

    resolve: {
        alias: {
            utilities: path.resolve(__dirname, './assets/js/'),
        },
    },
};
