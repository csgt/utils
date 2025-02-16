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
                <label>Permisos</label>
                <div class="row">
                    <div v-for="m in data.modules" class="col-sm-4">
                        <catalogs-rolemodule :module="m" />
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
            loading: true,
            saving: false,
        };
    },
    props: ["id", "path"],
    mounted() {
        axios
            .get(this.path + "/" + this.id + "/detail")
            .then((response) => {
                this.loading = false;
                this.data.role = response.data.role;
                this.data.modules = response.data.modules;
            })
            .catch((e) => {
                this.loading = false;
                alert(e);
            });
    },
    methods: {
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
