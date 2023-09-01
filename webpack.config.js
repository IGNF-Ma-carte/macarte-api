const path = require('path');
const CopyPlugin = require('copy-webpack-plugin')


module.exports = {
    entry: {
        app: path.resolve(__dirname, './assets/app.js'),
        api_connect: path.resolve(__dirname, './assets/api_connect.js'),
        default_contact: path.resolve(__dirname, './assets/default_contact.js'),
        default_home: path.resolve(__dirname, './assets/default_home.js'),
        default_edugeo: path.resolve(__dirname, './assets/default_edugeo.js'),
        admin_map_index: path.resolve(__dirname, './assets/admin_map_index.js'),
        admin_map_view: path.resolve(__dirname, './assets/admin_map_view.js'),
        admin_article_add: path.resolve(__dirname, './assets/admin_article_add.js'),
        admin_article_image: path.resolve(__dirname, './assets/admin_article_image.js'),
        admin_article_view: path.resolve(__dirname, './assets/admin_article_view.js'),
        admin_notif_add: path.resolve(__dirname, './assets/admin_notif_add.js'),
        error404: path.resolve(__dirname, './assets/error404.js'),
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, './public/build'),
    },
    module: {
        rules: [
            { 
                test: /\.scss?$/, 
                exclude: /node_modules/, 
                // loader: ["style-loader", "css-loader", "sass-loader"] },
                use: [
                    // Creates `style` nodes from JS strings
                    "style-loader",
                    // Translates CSS into CommonJS
                    "css-loader",
                    // Compiles Sass to CSS
                    "sass-loader",
                ],
            },
            { 
                test: /\.css?$/, 
                //exclude: /node_modules/, 
                // loader: ["style-loader", "css-loader", "sass-loader"] },
                use: [
                    // Creates `style` nodes from JS strings
                    "style-loader",
                    // Translates CSS into CommonJS
                    "css-loader",
                ],
            },
            {
                test: /\.html$/i,
                use: ["html-loader"],
            },
            {
                test: /\.txt$/i,
                use: 'raw-loader',
            },
            {
                test: /\.(png|jpg|gif)$/i,
                type: 'asset/resource',
            },
        ]
    },
    // plugins: [
    //     new CopyPlugin({
    //         patterns: [
    //             { from: './node_modules/mcutils/charte/boussole.png', to: './build' },
    //             // { from: "source", to: "dest" },
    //             // { from: "other", to: "public" },
    //         ],
    //     }),
    // ],
};