import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
  // Base JavaScript configuration
  js.configs.recommended,
  
  // Vue.js configuration for Vue 2
  ...vue.configs['flat/essential'],
  
  {
    files: ['**/*.{js,vue}'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node,
        // Laravel Mix globals
        mix: 'readonly',
        // jQuery and Bootstrap globals
        $: 'readonly',
        jQuery: 'readonly',
        bootstrap: 'readonly',
        // Laravel Echo
        Echo: 'readonly',
        // Common browser APIs
        process: 'readonly'
      }
    },
    rules: {
      // Vue-specific rules for Vue 2
      'vue/multi-word-component-names': 'off',
      'vue/no-v-model-argument': 'off',
      'vue/no-multiple-template-root': 'error',
      
      // General JavaScript rules
      'no-console': 'warn',
      'no-debugger': 'warn',
      'no-unused-vars': ['error', { 
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_' 
      }],
      'prefer-const': 'error',
      'no-var': 'error',
      
      // Code style
      'indent': ['error', 2],
      'quotes': ['error', 'single'],
      'semi': ['error', 'always'],
      'comma-dangle': ['error', 'never'],
      'object-curly-spacing': ['error', 'always'],
      'array-bracket-spacing': ['error', 'never'],
      
      // Best practices
      'eqeqeq': 'error',
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-new-func': 'error'
    }
  },
  
  // Specific configuration for Vue files
  {
    files: ['**/*.vue'],
    rules: {
      'vue/html-indent': ['error', 2],
      'vue/html-self-closing': ['error', {
        html: {
          void: 'never',
          normal: 'any',
          component: 'always'
        }
      }],
      'vue/max-attributes-per-line': ['error', {
        singleline: 3,
        multiline: 1
      }]
    }
  },
  
  // Configuration for Laravel Mix and build files
  {
    files: ['webpack.mix.js', 'webpack.config.js'],
    languageOptions: {
      globals: {
        ...globals.node
      }
    },
    rules: {
      'no-console': 'off'
    }
  },
  
  // Ignore patterns
  {
    ignores: [
      'node_modules/**',
      'vendor/**',
      'public/js/**',
      'public/css/**',
      'storage/**',
      'bootstrap/cache/**',
      '.ddev/**'
    ]
  }
];
