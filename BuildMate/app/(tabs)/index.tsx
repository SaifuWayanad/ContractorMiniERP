import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, StyleSheet, RefreshControl,
  TouchableOpacity, ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useFocusEffect } from 'expo-router';
import { Card } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { Badge } from '@/components/ui/Badge';
import { useAuth } from '@/hooks/useAuth';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type DashboardData = {
  summary: {
    projects: number;
    active_projects: number;
    completed_projects: number;
    pending_works: number;
    clients: number;
    locations: number;
  };
  todays_meetings: Array<{ id: number; name: string; client: string; next_meeting_at: string; next_meeting_notes: string | null }>;
  upcoming_meetings: Array<{ id: number; name: string; client: string; next_meeting_at: string; next_meeting_notes: string | null }>;
  recent_projects: Array<{ id: number; name: string; project_number: string; status: string; client: string }>;
  upcoming_deadlines: Array<{ type: string; title: string; subtitle: string | null; date: string; days: number }>;
};

const IST_MS = (5 * 60 + 30) * 60 * 1000;
const MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function istDate(iso: string): Date {
  const s = iso.replace(' ', 'T');
  const d = new Date((!s.match(/Z$/i) && !s.match(/[+-]\d{2}:\d{2}$/)) ? s + 'Z' : s);
  return new Date(d.getTime() + IST_MS);
}
function formatDate(iso: string) {
  const ist = istDate(iso);
  return `${String(ist.getUTCDate()).padStart(2,'0')} ${MONTHS_SHORT[ist.getUTCMonth()]} ${ist.getUTCFullYear()}`;
}
function formatTime(iso: string) {
  const ist = istDate(iso);
  const h = ist.getUTCHours(), m = ist.getUTCMinutes();
  const ampm = h >= 12 ? 'PM' : 'AM';
  return `${h % 12 || 12}:${String(m).padStart(2,'0')} ${ampm}`;
}

export default function DashboardScreen() {
  const { user } = useAuth();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/dashboard');
      setData(res.data);
    } catch {
      // keep showing last data on error
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));
  useEffect(() => { load(); }, [load]);

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={Colors.accent} />
      </View>
    );
  }

  const s = data?.summary;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        showsVerticalScrollIndicator={false}
      >
        {/* Greeting */}
        <View style={styles.greeting}>
          <View style={{ flex: 1, marginRight: 12 }}>
            <Text style={styles.greetingText}>Welcome, {user?.name?.split(' ')[0]} 👋</Text>
            <Text style={styles.greetingDate}>{new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' })}</Text>
          </View>
        </View>

        {/* Stat cards */}
        <View style={styles.statsRow}>
          <StatCard label="Projects" value={s?.projects ?? 0} accent="primary" style={{ marginRight: 6 }} />
          <StatCard label="Active" value={s?.active_projects ?? 0} accent="success" style={{ marginLeft: 6 }} />
        </View>
        <View style={styles.statsRow}>
          <StatCard label="Pending Works" value={s?.pending_works ?? 0} accent="warning" style={{ marginRight: 6 }} />
          <StatCard label="Clients" value={s?.clients ?? 0} accent="info" style={{ marginLeft: 6 }} />
        </View>

        {/* Today's meetings */}
        <Card title="📅 Today's Meetings" style={styles.meetingCard}>
          {(data?.todays_meetings?.length ?? 0) === 0 ? (
            <View style={styles.emptyMeeting}>
              <Ionicons name="calendar-outline" size={28} color={Colors.textMuted} />
              <Text style={styles.emptyMeetingText}>No meetings scheduled for today</Text>
            </View>
          ) : (
            data!.todays_meetings.map((m) => (
              <TouchableOpacity key={m.id} style={styles.meetingItem} onPress={() => router.push(`/(tabs)/projects/${m.id}`)}>
                <View style={styles.meetingTime}>
                  <Text style={styles.meetingTimeText}>{formatTime(m.next_meeting_at)}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.meetingTitle} numberOfLines={1}>{m.name}</Text>
                  <Text style={styles.meetingMeta} numberOfLines={1}>{m.client}{m.next_meeting_notes ? ` · ${m.next_meeting_notes}` : ''}</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} />
              </TouchableOpacity>
            ))
          )}
        </Card>

        {/* Upcoming meetings */}
        <Card title="🗓 Upcoming Meetings">
          {(data?.upcoming_meetings?.length ?? 0) === 0 ? (
            <View style={styles.emptyMeeting}>
              <Ionicons name="time-outline" size={28} color={Colors.textMuted} />
              <Text style={styles.emptyMeetingText}>No upcoming meetings scheduled</Text>
            </View>
          ) : (
            data!.upcoming_meetings.map((m) => (
              <TouchableOpacity key={m.id} style={styles.meetingItem} onPress={() => router.push(`/(tabs)/projects/${m.id}`)}>
                <View style={styles.dateBox}>
                  <Text style={styles.dateDay}>{istDate(m.next_meeting_at).getUTCDate()}</Text>
                  <Text style={styles.dateMonth}>{MONTHS_SHORT[istDate(m.next_meeting_at).getUTCMonth()]}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.meetingTitle} numberOfLines={1}>{m.name}</Text>
                  <Text style={styles.meetingMeta} numberOfLines={1}>{m.client} · {formatTime(m.next_meeting_at)}</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} />
              </TouchableOpacity>
            ))
          )}
        </Card>

        {/* Recent projects */}
        <Card
          title="Recent Projects"
          titleRight={
            <TouchableOpacity onPress={() => router.push('/(tabs)/projects/index')}>
              <Text style={styles.viewAll}>View all</Text>
            </TouchableOpacity>
          }
        >
          {(data?.recent_projects ?? []).length === 0 ? (
            <Text style={styles.empty}>No projects yet.</Text>
          ) : (
            data!.recent_projects.map((p) => (
              <TouchableOpacity key={p.id} style={styles.projectRow} onPress={() => router.push(`/(tabs)/projects/${p.id}`)}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.projectName} numberOfLines={1}>{p.name}</Text>
                  <Text style={styles.projectSub}>{p.project_number} · {p.client}</Text>
                </View>
                <Badge status={p.status} />
              </TouchableOpacity>
            ))
          )}
        </Card>

        {/* Upcoming deadlines */}
        {(data?.upcoming_deadlines?.length ?? 0) > 0 && (
          <Card title="⚠️ Upcoming Deadlines">
            {data!.upcoming_deadlines.map((d, i) => (
              <View key={i} style={styles.deadlineRow}>
                <View style={styles.dateBox}>
                  <Text style={styles.dateDay}>{new Date(d.date).getDate()}</Text>
                  <Text style={styles.dateMonth}>{new Date(d.date).toLocaleDateString('en-IN', { month: 'short' })}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.projectName} numberOfLines={1}>{d.title}</Text>
                  {d.subtitle && <Text style={styles.projectSub} numberOfLines={1}>{d.type} · {d.subtitle}</Text>}
                </View>
                <Badge
                  status={d.days < 0 ? 'high' : d.days <= 2 ? 'medium' : 'low'}
                  label={d.days < 0 ? `${Math.abs(d.days)}d late` : d.days === 0 ? 'Today' : `${d.days}d`}
                />
              </View>
            ))}
          </Card>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  scroll: { flex: 1 },
  content: { padding: 16, paddingBottom: 32 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.background },
  greeting: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  greetingText: { fontSize: 20, fontWeight: '800', color: Colors.text },
  greetingDate: { fontSize: 13, color: Colors.textSecondary, marginTop: 2 },
  statsRow: { flexDirection: 'row', marginBottom: 8 },
  meetingCard: { borderLeftWidth: 3, borderLeftColor: Colors.accent },
  meetingItem: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  meetingTime: { backgroundColor: Colors.accent + '20', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, marginRight: 12, minWidth: 72, alignItems: 'center' },
  meetingTimeText: { fontSize: 13, fontWeight: '700', color: Colors.accent },
  meetingTitle: { fontSize: 14, fontWeight: '700', color: Colors.text },
  meetingMeta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  dateBox: { alignItems: 'center', backgroundColor: Colors.primary + '10', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, marginRight: 12, minWidth: 48 },
  dateDay: { fontSize: 18, fontWeight: '800', color: Colors.primary },
  dateMonth: { fontSize: 10, fontWeight: '600', color: Colors.textSecondary, textTransform: 'uppercase' },
  viewAll: { fontSize: 13, color: Colors.accent, fontWeight: '600' },
  projectRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  projectName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  projectSub: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  deadlineRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  empty: { color: Colors.textMuted, fontSize: 13, textAlign: 'center', paddingVertical: 16 },
  emptyMeeting: { alignItems: 'center', paddingVertical: 20, gap: 8 },
  emptyMeetingText: { fontSize: 13, color: Colors.textMuted, textAlign: 'center' },
});
