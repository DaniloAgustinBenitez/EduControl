// Configuración de ESLint: reglas de Expo más Prettier para el formato.
const { defineConfig } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');
const prettierRecomendado = require('eslint-plugin-prettier/recommended');

module.exports = defineConfig([
  expoConfig,
  prettierRecomendado,
  {
    ignores: ['dist/*', 'coverage/*', '.expo/*'],
  },
]);
