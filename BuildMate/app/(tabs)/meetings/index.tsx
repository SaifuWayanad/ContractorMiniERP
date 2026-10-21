import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, SectionList, StyleSheet, TouchableOpacity,
  RefreshControl, ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { EmptyState } from '@/components/ui/EmptyState';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type MeetingProject = {
  id: number;
  name: string;
  client: string;
  next_meeting_at: string;
  next_meeting_notes: string | null;
};

const IST_MS = (5 * 60 + 30) * 60 * 1000;
const MONTHS_S = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const MONTHS_L = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS_L = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const DAYS_S = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

function istDate(iso: string): Date {
  const s = iso.replace(' ', 'T');
  const d = new Date((!s.match(/Z$/i) && !s.match(/[+-]\d{2}:\d{2}$/)) ? s + 'Z' : s);
  return new Date(d.getTime() + IST_MS);
}
function fmtTime(iso: string) {
  const ist = istDate(iso);
  const h = ist.getUTCHours(), m = ist.getUTCMinutes();
  const ampm = h >= 12 ? 'PM' : 'AM';
  return `${h % 12 || 12}:${String(m).padStart(2,'0')} ${ampm}`;
}
function fmtDate(iso: string) {
  const ist = istDate(iso);
  return `${DAYS_S[ist.getUTCDay()]}, ${String(ist.getUTCDate()).padStart(2,'0')} ${MONTHS_S[ist.getUTCMonth()]} ${ist.getUTCFullYear()}`;
}
function sectionKey(iso: string) {
  const ist = istDate(iso);
  return `${DAYS_L[ist.getUTCDay()]}, ${String(ist.getUTCDate()).padStart(2,'0')} ${MONTHS_L[ist.getUTCMonth()]} ${ist.getUTCFullYear()}`;
}

export default function MeetingsScreen() {
  const [sections, setSections] = useState<{ title: string; data: MeetingProject[] }[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/meetings');
      const items: MeetingProject[] = res.data;
      // Group by date
      const grouped: Record<string, MeetingProject[]> = {};
      items.forEach((m) => {
        const key = sectionKey(m.next_meeting_at);
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(m);
      });
      setSections(Object.entries(grouped).map(([title, data]) => ({ title, data })));
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <SectionList
        sections={sections}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={sections.length === 0 ? { flex: 1 } : styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        ListEmptyComponent={<EmptyState icon="calendar-outline" title="No upcoming meetings" message="Set next meeting dates on your projects and they'll appear here." />}
        renderSectionHeader={({ section }) => {
          const isToday = section.title.includes(new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'long', year: 'numeric' }).replace(/\d+ /, '')) &&
            section.title.includes(new Date().toLocaleDateString('en-IN', { weekday: 'long' }));
          return (
            <View style={[styles.sectionHeader, isToday && styles.todayHeader]}>
              <Ionicons name={isToday ? 'today' : 'calendar-outline'} size={14} color={isToday ? Colors.accent : Colors.textSecondary} />
              <Text style={[styles.sectionTitle, isToday && styles.todayTitle]}>{section.title}</Text>
              {isToday && <View style={styles.todayBadge}><Text style={styles.todayBadgeText}>TODAY</Text></View>}
            </View>
          );
        }}
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => router.push(`/(tabs)/projects/${item.id}`)} activeOpacity={0.75}>
            <View style={styles.timeBox}>
              <Text style={styles.time}>{fmtTime(item.next_meeting_at)}</Text>
            </View>
            <View style={styles.rowLine} />
            <View style={{ flex: 1 }}>
              <Text style={styles.projectName} numberOfLines={1}>{item.name}</Text>
              <Text style={styles.clientName} numberOfLines={1}>{item.client}</Text>
              {item.next_meeting_notes && (
                <Text style={styles.notes} numberOfLines={1}>{item.next_meeting_notes}</Text>
              )}
            </View>
            <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} />
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  list: { paddingBottom: 32 },
  sectionHeader: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    paddingHorizontal: 16, paddingVertical: 10,
    backgroundColor: Colors.background, borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  todayHeader: { backgroundColor: Colors.accent + '15' },
  sectionTitle: { fontSize: 13, fontWeight: '700', color: Colors.textSecondary, flex: 1 },
  todayTitle: { color: Colors.accent },
  todayBadge: { backgroundColor: Colors.accent, borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2 },
  todayBadgeText: { fontSize: 9, fontWeight: '800', color: Colors.primary, letterSpacing: 0.5 },
  row: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.white, paddingHorizontal: 16, paddingVertical: 14,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  timeBox: { minWidth: 64, marginRight: 12 },
  time: { fontSize: 13, fontWeight: '700', color: Colors.primary },
  rowLine: { width: 2, height: 36, backgroundColor: Colors.accent, borderRadius: 2, marginRight: 12 },
  projectName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  clientName: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  notes: { fontSize: 11, color: Colors.textMuted, marginTop: 2, fontStyle: 'italic' },
});
