import { nodeResolve } from "@rollup/plugin-node-resolve";
import rollupPolyfill from "rollup-plugin-polyfill-node";
import commonjs from "@rollup/plugin-commonjs";
import json from "@rollup/plugin-json";
import typescript from "@rollup/plugin-typescript";
import gzip from "rollup-plugin-gzip";
import brotli from "rollup-plugin-brotli";

export default {
  input: "src/index.ts",
  output: {
    file: "js/kujira-public.js",
    format: "cjs",
  },
  plugins: [
    commonjs(),
    {
      ...rollupPolyfill({ include: null }),
      // Make sure we do this _after_ the CJS analysis so that
      // the import statement doesn't confuse things
      enforce: "post",
    },
    typescript(),
    nodeResolve(),
    json(),
    gzip(),
    brotli(),
    // terser(),
  ],
};
