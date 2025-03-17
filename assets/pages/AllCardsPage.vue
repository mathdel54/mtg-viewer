<script setup>
import { onMounted, ref } from 'vue';
import { fetchAllCards } from '../services/cardService';

const cards = ref([]);
const loadingCards = ref(true);
const page = ref(1);

async function loadCards(id = 1) {
    loadingCards.value = true;
    cards.value = await fetchAllCards(id);
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
});

</script>

<template>
    <div>
        <h1>Toutes les cartes</h1>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <div class="card-result" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.id }} - {{ card.name }} <span>({{ card.uuid }})</span>
                </router-link>
            </div>
        </div>
        <button type="button" @click="previousPage()">Page précédente</button>
        <button type="button" @click="nextPage()">Page suivante</button>
    </div>
</template>
