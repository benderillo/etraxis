const glob = require('glob');
const path = require('path');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

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
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
        ],
    },

    resolve: {
        alias: {
            components: path.resolve(__dirname, './assets/vue/'),
            utilities: path.resolve(__dirname, './assets/js/'),
        },
    },

    plugins: [
        new VueLoaderPlugin(),
    ],
};
