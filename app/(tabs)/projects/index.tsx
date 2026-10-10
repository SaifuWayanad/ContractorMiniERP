import React, { useCallback, useEffect, useState } from 'react';
import { useFocusEffect } from 'expo-router';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  TextInput, RefreshControl, ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/EmptyState';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type Project = {
  id: number;
  name: string;
  project_number: string;
  status: string;
  client: string;
  start_date: string | null;
  next_meeting_at: string | null;
};

function FAB({ onPress }: { onPress: () => void }) {
  return (
    <TouchableOpacity style={fab.btn} onPress={onPress} activeOpacity={0.85}>
      <Ionicons name="add" size={28} color={Colors.primary} />
    </TouchableOpacity>
  );
}

export default function ProjectsScreen() {
  const [projects, setProjects] = useState<Project[]>([]);
  const [filtered, setFiltered] = useState<Project[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/projects');
      setProjects(res.data.data ?? res.data);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  useEffect(() => {
    const q = search.toLowerCase();
    setFiltered(q ? projects.filter((p) => p.name.toLowerCase().includes(q) || p.project_number.toLowerCase().includes(q) || p.client.toLowerCase().includes(q)) : projects);
  }, [search, projects]);

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <FAB onPress={() => router.push('/(tabs)/projects/form')} />
      <View style={styles.searchBar}>
        <Ionicons name="search" size={18} color={Colors.textMuted} style={{ marginRight: 8 }} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Search projects…"
          placeholderTextColor={Colors.textMuted}
          clearButtonMode="while-editing"
        />
      </View>
      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        contentContainerStyle={filtered.length === 0 ? { flex: 1 } : styles.list}
        ListEmptyComponent={<EmptyState icon="business-outline" title="No projects found" message="Projects you create appear here." />}
        ItemSeparatorComponent={() => <View style={styles.sep} />}
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => router.push(`/(tabs)/projects/${item.id}`)} activeOpacity={0.75}>
            <View style={{ flex: 1 }}>
              <View style={styles.rowTop}>
                <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
                <Badge status={item.status} />
              </View>
              <Text style={styles.sub}>{item.project_number} · {item.client}</Text>
              {item.next_meeting_at && (
                <View style={styles.meetingChip}>
                  <Ionicons name="calendar-outline" size={12} color={Colors.accent} />
                  <Text style={styles.meetingChipText}>
                    {new Date(item.next_meeting_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })} · {new Date(item.next_meeting_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })}
                  </Text>
                </View>
              )}
            </View>
            <Ionicons name="chevron-forward" size={18} color={Colors.textMuted} style={{ marginLeft: 8 }} />
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

const fab = StyleSheet.create({
  btn: {
    position: 'absolute', bottom: 24, right: 20, zIndex: 10,
    width: 56, height: 56, borderRadius: 28,
    backgroundColor: Colors.accent, alignItems: 'center', justifyContent: 'center',
    shadowColor: Colors.accent, shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.4, shadowRadius: 8, elevation: 8,
  },
});

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  searchBar: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.white, margin: 12, paddingHorizontal: 12,
    borderRadius: 10, height: 42,
    shadowColor: Colors.shadow, shadowOffset: { width: 0, height: 1 }, shadowOpacity: 1, shadowRadius: 3, elevation: 2,
  },
  searchInput: { flex: 1, fontSize: 15, color: Colors.text },
  list: { padding: 12 },
  row: {
    backgroundColor: Colors.white, borderRadius: 12, padding: 14,
    flexDirection: 'row', alignItems: 'center',
    shadowColor: Colors.shadow, shadowOffset: { width: 0, height: 1 }, shadowOpacity: 1, shadowRadius: 3, elevation: 2,
  },
  sep: { height: 8 },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  name: { fontSize: 15, fontWeight: '700', color: Colors.text, flex: 1, marginRight: 8 },
  sub: { fontSize: 12, color: Colors.textSecondary },
  meetingChip: { flexDirection: 'row', alignItems: 'center', marginTop: 6, gap: 4 },
  meetingChipText: { fontSize: 11, color: Colors.accent, fontWeight: '600' },
});
