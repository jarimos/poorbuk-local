import objectAssign from 'object-assign';
import Promise from 'promise/lib/es6-extensions';

import './peepso';

import observer from './observer';
import * as browser from './browser';
import './external-link';
import util from './util';

objectAssign( peepso, {
	objectAssign,
	Promise,

	browser,
	observer,
	util
} );

import '../npm-expanded';
import '../pswindow';
import '../peepso';

import login from './login';
import sse from './sse';
objectAssign( peepso, { login, sse } );

import modules from '../modules';
objectAssign( peepso, { modules } );

// PeepSo UI functionalities initialization.
import '../sections';
