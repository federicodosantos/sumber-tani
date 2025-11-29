import './bootstrap';

import Alpine from 'alpinejs';
import  persist  from '@alpinejs/persist';
import AutoNumeric from 'autonumeric';

import cashierHandler from './cashier';

Alpine.plugin(persist);

Alpine.data('cashierHandler', cashierHandler);

window.AutoNumeric = AutoNumeric;

window.Alpine = Alpine;
Alpine.start();
