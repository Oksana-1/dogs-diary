import { createApp } from 'vue';
import AppDogList from './js/modules/dogsDiary/view/AppDogList.js';
import AppDogDetail from './js/modules/dogsDiary/view/AppDogDetail.js';
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
