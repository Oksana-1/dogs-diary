import { createApp } from 'vue';
import AppDogList from './js/modules/dogsDiary/view/AppDogList.js';
import AppDogDetail from './js/modules/dogsDiary/view/AppDogDetail.js';
import AppLogin from './js/modules/dogsDiary/view/AppLogin.js';
import AppSignUp from './js/modules/dogsDiary/view/AppSignUp.js';
import AppResetPasswordRequest from './js/modules/dogsDiary/view/AppResetPasswordRequest.js';
import AppResetPasswordCheckEmail from './js/modules/dogsDiary/view/AppResetPasswordCheckEmail.js';
import AppResetPassword from './js/modules/dogsDiary/view/AppResetPassword.js';
import './styles/app.css';

const APPS = [
    {
        id: 'dog-list-app',
        component: AppDogList,
    },
    {
        id: 'dog-detail-app',
        component: AppDogDetail,
    },
    {
        id: 'login-app',
        component: AppLogin,
    },
    {
        id: 'sign-up-app',
        component: AppSignUp,
    },
    {
        id: 'reset-password-request-app',
        component: AppResetPasswordRequest,
    },
    {
        id: 'reset-password-check-email-app',
        component: AppResetPasswordCheckEmail,
    },
    {
        id: 'reset-password-app',
        component: AppResetPassword,
    },
];

for (const { id, component } of APPS) {
    const root = document.getElementById(id);

    if (!root) {
        continue;
    }

    const props = root.dataset.props
        ? JSON.parse(root.dataset.props)
        : {};

    createApp(component, props).mount(root);
}
