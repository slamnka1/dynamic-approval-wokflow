import axios from 'axios';

const client = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

client.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

client.interceptors.response.use(
    (r) => r,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            if (!window.location.pathname.startsWith('/login')) {
                window.location.assign('/login');
            }
        }
        return Promise.reject(error);
    },
);

export default client;
