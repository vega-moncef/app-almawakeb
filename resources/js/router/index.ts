import {createRouter, createWebHistory} from 'vue-router'
import {allRoutes} from "@/router/routes";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: allRoutes
})

router.beforeEach((to, from, next) => {
    const title = to.meta.title;
    if (title) {
        document.title = title.toString();
    }
    next();
})

export default router
