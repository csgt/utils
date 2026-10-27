<template>
    <div class="card">
        <div v-if="loading" class="text-center">
            <div class="card-body">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>
        <template v-else>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-sm-12">
                        <label for="nombre">Nombre</label>
                        <input name="nombre" type="text" class="form-control mb-2" v-model="data.role.name" />
                    </div>
                    <div class="form-group col-sm-12">
                        <label for="descripcion">Descripción</label>
                        <input
                            name="descripcion"
                            type="text"
                            class="form-control mb-2"
                            v-model="data.role.description"
                        />
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3 mb-1">
                    <h5>Permisos</h5>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Seleccionar permisos">
                        <button type="button" class="btn btn-outline-success" @click="setAllPermissions(true)">
                            <i class="fa-regular fa-square-check text-success"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" @click="setAllPermissions(false)">
                            <i class="fa-regular fa-square text-danger"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div v-for="m in data.modules" :key="m.name" class="col-sm-4">
                        <catalogs-rolemodule :module="m" />
                    </div>
                </div>
                <div class="row mt-3" v-if="users.length">
                    <h5>Usuarios con este rol</h5>
                    <div class="col-6 col-sm-2" v-for="user in users">
                        <div class="card">
                            <div class="card-body pt-1 pb-1">
                                <a :href="userpath + '/' + user.id + '/edit'" target="_blank">
                                    {{ user.name }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary" @click="save" :disabled="saving">Guardar</button>
            </div>
        </template>
    </div>
</template>

<script>
import axios from "axios";
export default {
    data() {
        return {
            data: {
                role: { name: "", description: "" },
                modules: [],
            },
            users: [],
            loading: true,
            saving: false,
        };
    },
    props: ["id", "path", "userpath"],
    mounted() {
        axios
            .get(this.path + "/" + this.id + "/detail")
            .then((response) => {
                this.loading = false;
                this.data.role = response.data.role;
                this.data.modules = response.data.modules;
                this.users = response.data.users;
            })
            .catch((e) => {
                this.loading = false;
                alert(e);
            });
    },
    methods: {
        setAllPermissions(enabled) {
            this.data.modules.forEach((module) => {
                module.modulepermissions.forEach((permission) => {
                    permission.enabled = enabled;
                });
            });
        },
        save() {
            this.saving = true;
            if (this.id != 0) {
                axios
                    .patch(this.path + "/" + this.id, this.data)
                    .then((response) => {
                        window.location = this.path;
                    })
                    .catch((error) => {
                        this.saving = false;
                        alert(error.response.data.message);
                    });
            } else {
                axios
                    .post(this.path, this.data)
                    .then((response) => {
                        window.location = this.path;
                    })
                    .catch((error) => {
                        this.saving = false;
                        alert(error.response.data.message);
                    });
            }
        },
    },
};
</script>
