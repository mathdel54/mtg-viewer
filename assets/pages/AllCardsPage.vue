<script setup>
import { onMounted, ref, watch } from 'vue';
import { fetchAllCards, fetchAllSetCodes } from '../services/cardService';

const cards = ref([]);
const loadingCards = ref(true);
const page = ref(1);
const setcodes = ref([]);
const filteredCards = ref([]);
const choosedSetCode = ref('');

function filterCardsBySet() {
    filteredCards.value = choosedSetCode.value ? cards.value.filter((card) => card.setCode === choosedSetCode.value) : filteredCards.value = [...cards.value];
}

async function loadSetCodes() {
    setcodes.value = await fetchAllSetCodes();
}

watch(choosedSetCode, () => {
    filterCardsBySet();
});

async function loadCards(id = 1) {
    loadingCards.value = true;
    cards.value = await fetchAllCards(id);
    filterCardsBySet();
    loadingCards.value = false;
}

function nextPage() {
    page.value += 1;
    loadCards(page.value);
}

function previousPage() {
    page.value -= 1;
    loadCards(page.value);
}

onMounted(() => {
    loadCards();
    loadSetCodes();
});
</script>

<template>
    <div>
        <h1>Toutes les cartes</h1>
        <div id="filter">
            <label for="set-select">SetCode</label>
            <select id="set-select" v-model="choosedSetCode">
                <option value="">Tous les sets</option>
                <option v-for="setcode in setcodes" :key="setcode">{{ setcode }}</option>
            </select>
        </div>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <div class="card-result" v-for="card in filteredCards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.id }} - {{ card.name }} <span>({{ card.uuid }})</span>
                </router-link>
            </div>
        </div>
        <button type="button" @click="previousPage()">Page précédente</button>
        <button type="button" @click="nextPage()">Page suivante</button>
    </div>
</template>
