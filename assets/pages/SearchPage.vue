<script setup>
import { ref, watch } from 'vue';
import { fetchCardsByName } from '../services/cardService';

const search = ref('');
const loadingCards = ref(false);
const cards = ref([]);
const hasSearched = ref(false);

async function loadCardsByName() {
    loadingCards.value = true;
    const fetchedCards = await fetchCardsByName(search.value);
    cards.value = fetchedCards.slice(0, 20);
    hasSearched.value = true;
    cards.value = [];
    loadingCards.value = false;
}

watch(search, async (newValue) => {
    if (newValue.length >= 3) {
        await loadCardsByName();
    } else {
        cards.value = [];
        hasSearched.value = false;
    }
});
</script>

<template>
    <div>
        <h1>Rechercher une Carte</h1>
        <div class="search-form">
            <label for="card-search">Recherche</label>
            <input type="text" id="card-search" v-model="search" placeholder="Entrez le nom d'une carte (min. 3 caractères)" minlength="3">
        </div>
    </div>
    <div class="card-list">
        <div v-if="!hasSearched && search.length < 3">
            <p>Entrez au moins 3 caractères pour lancer la recherche</p>
        </div>
        <div v-else-if="loadingCards">
            <p>Recherche en cours...</p>
        </div>
        <div v-else>
            <div v-if="cards.length === 0 && hasSearched">
                <p>Aucune carte trouvée</p>
            </div>
            <div v-else>
                <p v-if="hasSearched">Affichage des {{ cards.length }} premiers résultats</p>
                <div class="card" v-for="card in cards" :key="card.id">
                    <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                        {{ card.name }} - {{ card.uuid }}
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>
